<?php

namespace Export\ExportType;

use Espo\Core\Utils\Json;

/**
 * Class ProductImage
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductImage extends ProductAsset
{
   protected function getCustomWhere(): string
   {
       return ' AND type = \'Gallery Image\'';
   }
}
