<?php

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');
/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/).
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 *
 * @link      http://kazinduzi.com
 *
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 */
class Cart
{
    private $db = false;

    private $config = [];

    private $session = false;

    private $data = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->db = Kazinduzi::db();
        $this->config = Kazinduzi::getConfig();
        $this->session = Kazinduzi::session();
        if (!$this->session->get('cart') || !is_array($this->session->get('cart'))) {
            $this->session->add('cart', $data);
        }
    }

    /**
     * @param type  $productid
     * @param type  $qty
     * @param array $options
     *
     * @return \Cart|bool
     */
    public function add($productid, $qty = 1, array $options = null)
    {
        if (!$options) {
            $key = $productid;
        } else {
            $key = $productid.':'.base64_encode(serialize($options));
        }
        if (is_numeric($qty) && ((int) $qty > 0)) {
            if (!array_key_exists($key, $__cart_data = $this->session->get('cart'))) {
                $__cart_data[$key] = (int) $qty;
            } else {
                $__cart_data[$key] += (int) $qty;
            }
            $this->session->add('cart', $__cart_data);

            return $this;
        }

        return false;
    }

    /**
     * @param type $key
     * @param type $qty
     *
     * @return \Cart
     */
    public function update($key, $qty)
    {
        if ((int) $qty && ((int) $qty > 0) && array_key_exists($key, $data = $this->session->get('cart'))) {
            $data[$key] = (int) $qty;
            $this->session->add('cart', $data);
        } else {
            $this->remove($key);
        }

        return $this;
    }

    /**
     * Remove item from the cart.
     *
     * @param string $key
     *
     * @return \Cart
     */
    public function remove($key)
    {
        if (array_key_exists($key, $data = $this->session->get('cart'))) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
            $this->session->add('cart', $data);
        }

        return $this;
    }

    /**
     * Clear or destroy the cart.
     *
     * @return bool
     */
    public function clear()
    {
        if ($this->session->get('cart')) {
            $this->session->remove('cart');
        }

        return true;
    }

    /**
     * Alias for the clear method.
     *
     * @return bool
     */
    public function destroy()
    {
        $this->clear();

        return true;
    }

    /**
     * @todo to be implemented for the sub-total
     */
    public function getSubTotal()
    {
        return true;
    }

    /**
     * @todo
     */
    public function getTotal()
    {
        return true;
    }

    /**
     * Is there any products in the cart.
     *
     * @return bool
     */
    public function hasProducts()
    {
        return count($this->session->get('cart')) > 0;
    }
}
