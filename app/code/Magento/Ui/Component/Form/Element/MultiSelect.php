<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Ui\Component\Form\Element;

/**
 * Class MultiSelect
 */
class MultiSelect extends AbstractOptionsField
{
    const NAME = 'multiselect';

    const DEFAULT_SIZE = 6;

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $config['size'] = self::DEFAULT_SIZE;
        $this->setData('config', array_replace_recursive((array)$this->getData('config'), $config));
        parent::prepare();
    }

    /**
     * Get component name
     *
     * @return string
     */
    public function getComponentName()
    {
        return static::NAME;
    }
}
