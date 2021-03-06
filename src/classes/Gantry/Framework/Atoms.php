<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

use Gantry\Component\File\CompiledYamlFile;
use RocketTheme\Toolbox\ArrayTraits\ArrayAccess;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\ExportInterface;
use RocketTheme\Toolbox\ArrayTraits\Iterator;

class Atoms implements \ArrayAccess, \Iterator, ExportInterface
{
    use ArrayAccess, Iterator, Export;

    /**
     * @var array
     */
    protected $items;

    /**
     * @var array
     */
    protected $ids;

    /**
     * @var array|static[]
     */
    protected static $instances;

    /**
     * @param string $outline
     * @return static
     */
    public static function instance($outline)
    {
        if (!isset(static::$instances[$outline])) {
            $file = CompiledYamlFile::instance("gantry-theme://config/{$outline}/page/head.yaml");
            $head = $file->content();
            static::$instances[$outline] = new static(isset($head['atoms']) ? $head['atoms'] : []);
            $file->free();

            static::$instances[$outline]->init();
        }

        return static::$instances[$outline];
    }

    /**
     * Atoms constructor.
     * @param array $atoms
     */
    public function __construct(array $atoms = [])
    {
        $this->items = $atoms;

        foreach ($this->items as &$item) {
            if (!empty($item['id'])) {
                $this->ids[$item['id']] = $item;
            }
        }
    }

    public function init()
    {
        foreach ($this->items as &$item) {
            if (!empty($item['inherit']['outline']) && !empty($item['inherit']['atom'])) {
                $inherited = static::instance($item['inherit']['outline']);
                $test = $inherited->id($item['inherit']['atom']);
                if (!empty($test['attributes'])) {
                    $item['attributes'] = $test['attributes'];
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function update()
    {
        foreach ($this->items as &$item) {
            if (empty($item['id'])) {
                $item['id'] = $this->createId($item);
            }
            if (!empty($item['inherit']['outline']) && !empty($item['inherit']['atom'])) {
                unset($item['attributes']);
            } else {
                unset($item['inherit']);
            }
        }

        return $this;
    }

    /**
     * @param string $outline
     * @return $this
     */
    public function inheritAll($outline)
    {
        foreach ($this->items as &$item) {
            if (!empty($item['id'])) {
                $item['inherit'] = [
                    'outline' => $outline,
                    'atom' => $item['id'],
                    'include' => ['attributes']
                ];
            }
        }

        return $this;
    }

    /**
     * @param string $id
     * @return array
     */
    public function id($id)
    {
        return isset($this->ids[$id]) ? $this->ids[$id] : [];
    }

    /**
     * @param string $type
     * @return array
     */
    public function type($type)
    {
        $list = [];
        foreach ($this->items as $item) {
            if ($item['type'] === $type) {
                $list[] = $item;
            }
        }

        return $list;
    }

    /**
     * @param array $item
     * @return string
     */
    protected function createId(array &$item)
    {
        $type = $item['type'];

        while ($num = rand(1000, 9999)) {
            if (!isset($this->ids["{$type}-{$num}"])) {
                break;
            }
        }

        $id = "{$type}-{$num}";

        $this->ids[$id] = $item;

        return $id;
    }
}
