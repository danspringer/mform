<?php
/**
 * @author Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace MForm\Utils;


use MForm\DTO\MFormItem;

class MFormGroupExtensionHelper
{
    /**
     * @param MFormItem[] $items
     * @return MFormItem[]
     * @author Joachim Doerr
     */
    public static function addTabGroupExtensionItems(array $items): array
    {
        return self::addGroupExtensionItems($items, 'tab');
    }

    /**
     * @param MFormItem[] $items
     * @return MFormItem[]
     * @author Joachim Doerr
     */
    public static function addColumnGroupExtensionItems(array $items): array
    {
        return self::addGroupExtensionItems($items, 'column');
    }

    /**
     * @param MFormItem[] $items
     * @return MFormItem[]
     * @author Joachim Doerr
     */
    public static function addInlineGroupExtensionItems(array $items): array
    {
        return self::addGroupExtensionItems($items, 'inline');
    }

    /**
     * @param MFormItem[] $items
     * @return MFormItem[]
     * @author Joachim Doerr
     */
    public static function addCollapseGroupExtensionItems(array $items): array
    {
        return self::addGroupExtensionItems($items, 'collapse');
    }

    /**
     * @param MFormItem[] $items
     * @return MFormItem[]
     * @author Joachim Doerr
     */
    public static function addAccordionGroupExtensionItems(array $items): array
    {
        return self::addGroupExtensionItems($items, 'accordion');
    }

    /**
     * @param array $items
     * @param string $type
     * @return array
     * @author Joachim Doerr
     */
    private static function addGroupExtensionItems(array $items, string $type): array
    {
        $newItems = array();
        $key = 0;
        $groupKey = 0;
        $groupCount = 0;
        $count = 1;
        $group = false;
        $toggleAttributes = false;

        /** @var MFormItem $item */
        foreach ($items as $key => $item) {

            switch ($item->getType()) {
                case 'checkbox':
                    if (array_key_exists('data-toggle', $item->getAttributes())) {
                        $toggleAttributes = $item->getAttributes();
                        unset($toggleAttributes['data-mform-toggle']);
                    }
                case 'select':
                    if (array_key_exists('data-toggle', $item->getAttributes())) {
                        $toggleAttributes = $item->getAttributes();
                    }
                default:
                    // add default item
                    $newItems[] = $item;
                    break;
                case $type:
                    // count by typ
                    $count++;

                    if (!$group) {
                        $group = true;
                        $count = 1; // reset count by type for group
                        $groupCount++; // count by group
                        $groupKey = uniqid($groupCount, true);

                        // open the new group before the group item will be added to the item list
                        if (is_array($toggleAttributes)) {
                            $mergeArray = array('data-group-select-accordion' => ($toggleAttributes['data-toggle'] == 'accordion') ? 'true' : 'false');
                            if (array_key_exists('hide-toggle-links', $toggleAttributes)) {
                                $mergeArray['data-group-hide-toggle-links'] = ($toggleAttributes['hide-toggle-links']) ? 'true' : 'false';
                            }
                            $item->setAttributes(array_merge($item->getAttributes(), $toggleAttributes, $mergeArray));
                            $toggleAttributes = false;
                        }
                        // start group for type
                        $newItems[] = self::createGroupItem("start-group-$type", $groupCount, $count, $groupKey, $item);

                    } else {
                        // close prev item by same type in group
                        if ($items[$key - 1] instanceof MFormItem && $items[$key - 1]->type != "close-$type") { // is not closed by other item
                            // close auto
                            $newItems[] = self::createGroupItem("close-$type", $groupCount, ($count - 1), $groupKey, $item);
                        }
                    }

                    // add group counts and id
                    $item->setGroup($groupCount) // group id
                        ->setGroupCount($count) // count of group icons
                        ->setGroupKey($groupKey);

                    // open type
                    $newItems[] = $item;
                    break;
                case 'close-' . $type:
                    // add group counts and id
                    $item->setGroup($groupCount) // group id
                        ->setGroupCount($count) // count of group icons
                        ->setGroupKey($groupKey);

                    // close type in group
                    $newItems[] = $item;

                    if (isset($items[$key + 1]) && $items[$key + 1] instanceof MFormItem && $items[$key + 1]->type != $type or // next item is not from type close group
                        isset($item->getAttributes()["data-close-group-$type"]) && $item->getAttributes()["data-close-group-$type"] == 1
                    ) {
                        // close group auto
                        $newItems[] = self::createGroupItem("close-group-$type", $groupCount, $count, $groupKey, $item);
                        $group = false;
                    }
                    break;
            }
        }

        // group and item was not closed do it now
        if ($group) {
            if (isset($newItems[$key]) && $newItems[$key] instanceof MFormItem && $items[$key]->type != "close-$type") {
                $newItems[] = self::createGroupItem("close-$type", $groupCount, $count, $groupKey, $item);
            }
            $newItems[] = self::createGroupItem("close-group-$type", $groupCount, $count, $groupKey, $item);
        }

        return $newItems;
    }

    /**
     * @param string $type
     * @param int $group
     * @param int $groupCount
     * @param string $groupKey
     * @param MFormItem|null $item
     * @return MFormItem
     * @author Joachim Doerr
     */
    public static function createGroupItem(string $type, int $group = 0, int $groupCount = 0, string $groupKey = '0', MFormItem $item = null): MFormItem
    {
        $newItem = new MFormItem();

        if (!is_null($item) && is_array($item->getAttributes()) && sizeof($item->getAttributes()) > 0) {
            $attributes = array();
            foreach ($item->getAttributes() as $key => $value) {
                if (strpos($key, "data-group-") !== false) {
                    $attributes[$key] = $value;
                }
            }
            $newItem->setAttributes($attributes);
        }

        return $newItem->setType($type)
            ->setGroupCount($groupCount)
            ->setGroup($group)
            ->setGroupKey($groupKey);
    }
}