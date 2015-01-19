<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Ui\Component\Form\Element;

/**
 * Class Input
 */
class Input extends AbstractFormElement
{
    /**
     * @return mixed|string
     */
    public function getType()
    {
        return $this->getData('input_type') ? $this->getData('input_type') : 'text';
    }

    /**
     * @return void
     */
    public function prepare()
    {
        parent::prepare(); // TODO: Change the autogenerated stub
    }
}