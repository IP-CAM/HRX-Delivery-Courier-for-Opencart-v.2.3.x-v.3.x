<?php

/**
 * Box packing (3D bin packing, knapsack problem).
 *
 * @author Doug Wright
 */
// declare(strict_types=1);

namespace Mijora\DVDoug\BoxPacker;

/*
 * Rotation permutations
 */

class Rotation
{
    /* Must be placed in it's defined orientation only */
    const NEVER = 1;
    /* Can be turned sideways 90°, but cannot be placed *on* it's side e.g. fragile "↑this way up" items */
    const KEEP_FLAT = 2;
    /* No handling restrictions, item can be placed in any orientation */
    const BEST_FIT = 6;
}
