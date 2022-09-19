<?php

/**
 * Box packing (3D bin packing, knapsack problem).
 *
 * @author Doug Wright
 */
// declare(strict_types=1);

namespace Mijora\DVDoug\BoxPacker;

use function array_merge;
use function iterator_to_array;
use function max;
use function sort;

/**
 * Layer packer.
 * @internal
 */
class LayerPacker //implements LoggerAwareInterface
{
    private Box $box;

    private bool $singlePassMode = false;

    private OrientatedItemFactory $orientatedItemFactory;

    private bool $beStrictAboutItemOrdering = false;

    public function __construct(Box $box)
    {
        $this->box = $box;

        $this->orientatedItemFactory = new OrientatedItemFactory($this->box);
    }

    public function setSinglePassMode(bool $singlePassMode): void
    {
        $this->singlePassMode = $singlePassMode;
        $this->orientatedItemFactory->setSinglePassMode($singlePassMode);
    }

    public function beStrictAboutItemOrdering(bool $beStrict): void
    {
        $this->beStrictAboutItemOrdering = $beStrict;
    }

    /**
     * Pack items into an individual vertical layer.
     */
    public function packLayer(ItemList &$items, PackedItemList $packedItemList, int $startX, int $startY, int $startZ, int $widthForLayer, int $lengthForLayer, int $depthForLayer, int $guidelineLayerDepth, bool $considerStability): PackedLayer
    {
        $layer = new PackedLayer();
        $x = $startX;
        $y = $startY;
        $z = $startZ;
        $rowLength = 0;
        $prevItem = null;
        $skippedItems = [];
        $remainingWeightAllowed = $this->box->getMaxWeight() - $this->box->getEmptyWeight() - $packedItemList->getWeight();

        while ($items->count() > 0) {
            $itemToPack = $items->extract();

            // skip items that will never fit e.g. too heavy
            if ($itemToPack->getWeight() > $remainingWeightAllowed) {
                continue;
            }

            $orientatedItem = $this->orientatedItemFactory->getBestOrientation($itemToPack, $prevItem, $items, $widthForLayer - $x, $lengthForLayer - $y, $depthForLayer, $rowLength, $x, $y, $z, $packedItemList, $considerStability);

            if ($orientatedItem instanceof OrientatedItem) {
                $packedItem = PackedItem::fromOrientatedItem($orientatedItem, $x, $y, $z);
                $layer->insert($packedItem);
                $packedItemList->insert($packedItem);

                $rowLength = max($rowLength, $packedItem->getLength());
                $prevItem = $orientatedItem;

                // Figure out if we can stack items on top of this rather than side by side
                // e.g. when we've packed a tall item, and have just put a shorter one next to it.
                $stackableDepth = ($guidelineLayerDepth ?: $layer->getDepth()) - $packedItem->getDepth();
                if ($stackableDepth > 0) {
                    $stackedLayer = $this->packLayer($items, $packedItemList, $x, $y, $z + $packedItem->getDepth(), $x + $packedItem->getWidth(), $y + $packedItem->getLength(), $stackableDepth, $stackableDepth, $considerStability);
                    $layer->merge($stackedLayer);
                }

                $x += $packedItem->getWidth();
                $remainingWeightAllowed = $this->box->getMaxWeight() - $this->box->getEmptyWeight() - $packedItemList->getWeight(); // remember may have packed additional items

                if ($items->count() === 0 && $skippedItems) {
                    $items = ItemList::fromArray(array_merge($skippedItems, iterator_to_array($items)), true);
                    $skippedItems = [];
                }
                continue;
            }

            if (!$this->beStrictAboutItemOrdering && $items->count() > 0) { // skip for now, move on to the next item
                $skippedItems[] = $itemToPack;
                // abandon here if next item is the same, no point trying to keep going. Last time is not skipped, need that to trigger appropriate reset logic
                while ($items->count() > 1 && self::isSameDimensions($itemToPack, $items->top())) {
                    $skippedItems[] = $items->extract();
                }
                continue;
            }

            if ($x > $startX) {
                // Having now placed items, there is space *within the same row* along the length. Pack into that.
                $layer->merge($this->packLayer($items, $packedItemList, $x, $y + $rowLength, $z, $widthForLayer, $lengthForLayer - $rowLength, $depthForLayer, $layer->getDepth(), $considerStability));

                $y += $rowLength;
                $x = $startX;
                $rowLength = 0;
                $skippedItems[] = $itemToPack;
                $items = ItemList::fromArray(array_merge($skippedItems, iterator_to_array($items)), true);
                $skippedItems = [];
                $prevItem = null;
                continue;
            }

            $skippedItems[] = $itemToPack;

            $items = ItemList::fromArray(array_merge($skippedItems, iterator_to_array($items)), true);

            return $layer;
        }

        return $layer;
    }

    /**
     * Compare two items to see if they have same dimensions.
     */
    private static function isSameDimensions(Item $itemA, Item $itemB): bool
    {
        if ($itemA === $itemB) {
            return true;
        }
        $itemADimensions = [$itemA->getWidth(), $itemA->getLength(), $itemA->getDepth()];
        $itemBDimensions = [$itemB->getWidth(), $itemB->getLength(), $itemB->getDepth()];
        sort($itemADimensions);
        sort($itemBDimensions);

        return $itemADimensions === $itemBDimensions;
    }
}
