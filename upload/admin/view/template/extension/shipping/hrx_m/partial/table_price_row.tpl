<?php $price_country_code = $price->getCountryCodeValue() ?>
<tr data-price-row="<?php echo $price_country_code; ?>" data-price-data='<?php echo $price->getBase64String(); ?>'>
    <td><?php echo $price->getCountryNameValue(); ?></td>
    <td><?php echo $price->getPriceValue(); ?></td>
    <td><?php echo $price_range_types[$price->getRangeTypeValue()]; ?></td>
    <td><?php echo $price->getCourierPriceValue(); ?></td>
    <td><?php echo $price_range_types[$price->getCourierRangeTypeValue()]; ?></td>
    <td>
        <div class="hrx_m-actions-col">
            <button data-price-edit="<?php echo $price_country_code; ?>" class="btn btn-primary"><i class="fa fa-edit"></i></button>
            <button data-price-delete="<?php echo $price_country_code; ?>" class="btn btn-danger"><i class="fa fa-trash"></i></button>
        </div>
    </td>
</tr>