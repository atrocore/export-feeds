<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Export\Entities;

use Espo\Core\Templates\Entities\Base;
use Espo\Core\Utils\Json;

class ExportFeed extends Base
{
    public const DATA_FIELD = 'feedFields';

    protected $entityType = "ExportFeed";

    public function setFeedField(string $name, $value): void
    {
        $data = [];
        if (!empty($this->get('data'))) {
            $data = Json::decode(Json::encode($this->get('data')), true);
        }

        $data[self::DATA_FIELD][$name] = $value;

        $this->set('data', $data);
    }

    public function getFeedField(string $name)
    {
        $data = $this->getFeedFields();

        if (!isset($data[$name])) {
            return null;
        }

        return $data[$name];
    }

    public function getFeedFields(): array
    {
        if (!empty($data = $this->get('data'))) {
            $data = Json::decode(Json::encode($data), true);
            if (!empty($data[self::DATA_FIELD]) && is_array($data[self::DATA_FIELD])) {
                return $data[self::DATA_FIELD];
            }
        }

        return [];
    }
}
