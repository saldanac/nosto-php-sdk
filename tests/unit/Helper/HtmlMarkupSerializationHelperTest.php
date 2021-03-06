<?php
/**
 * Copyright (c) 2017, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <contact@nosto.com>
 * @copyright 2017 Nosto Solutions Ltd
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 *
 */

use Codeception\Specify;
use Codeception\TestCase\Test;
use Nosto\Helper\HtmlMarkupSerializationHelper;

class HtmlMarkupSerializationHelperTest extends Test
{
    use Specify;

    /**
     * Tests that an object is serialized to HTML correctly
     */
    public function testObject()
    {
        $object = new MockProduct();
        $markup = $object->toHtml();
        $this->assertEquals(self::stripLineBreaks($markup), '<div class="notranslate" style="display:none">  <span class="nosto_product" style="display:none">    <span class="url">http://my.shop.com/products/test_product.html</span>    <span class="product_id">1</span>    <span class="name">Test Product</span>    <span class="image_url">http://my.shop.com/images/test_product.jpg</span>    <span class="price">99.99</span>    <span class="list_price">110.99</span>    <span class="price_currency_code">USD</span>    <span class="availability">InStock</span>    <span class="categories">      <span class="category">/Mens</span>      <span class="category">/Mens/Shoes</span>    </span>    <span class="description">This is a full description</span>    <span class="brand">Super Brand</span>    <span class="variation_id">USD</span>    <span class="review_count">99</span>    <span class="rating_value">2.5</span>    <span class="alternate_image_urls">      <span class="alternate_image_url">http://shop.com/product_alt.jpg</span>    </span>    <span class="condition">Used</span>    <span class="gtin">gtin</span>    <span class="tags1">      <span class="tag">first</span>    </span>    <span class="tags2">      <span class="tag">second</span>    </span>    <span class="tags3">      <span class="tag">third</span>    </span>    <span class="google_category">All</span>    <span class="skus">    </span>    <span class="variations">    </span>  </span></div>');
    }

    /**
     * Tests that an object with custom fields is serialized to HTML correctly
     */
    public function testObjectWithCustomFields()
    {
        $object = new MockProduct();
        $object->addCustomField('customFieldNoSnakeCase', 'value');
        $markup = $object->toHtml();
        $this->assertEquals(self::stripLineBreaks($markup), '<div class="notranslate" style="display:none">  <span class="nosto_product" style="display:none">    <span class="url">http://my.shop.com/products/test_product.html</span>    <span class="product_id">1</span>    <span class="name">Test Product</span>    <span class="image_url">http://my.shop.com/images/test_product.jpg</span>    <span class="price">99.99</span>    <span class="list_price">110.99</span>    <span class="price_currency_code">USD</span>    <span class="availability">InStock</span>    <span class="categories">      <span class="category">/Mens</span>      <span class="category">/Mens/Shoes</span>    </span>    <span class="description">This is a full description</span>    <span class="brand">Super Brand</span>    <span class="variation_id">USD</span>    <span class="review_count">99</span>    <span class="rating_value">2.5</span>    <span class="alternate_image_urls">      <span class="alternate_image_url">http://shop.com/product_alt.jpg</span>    </span>    <span class="condition">Used</span>    <span class="gtin">gtin</span>    <span class="tags1">      <span class="tag">first</span>    </span>    <span class="tags2">      <span class="tag">second</span>    </span>    <span class="tags3">      <span class="tag">third</span>    </span>    <span class="google_category">All</span>    <span class="skus">    </span>    <span class="variations">    </span>    <span class="custom_fields">      <span class="customFieldNoSnakeCase">value</span>    </span>  </span></div>');
    }

    /**
     * Tests that an object with scandic custom fields is serialized to HTML correctly
     */
    public function testObjectWithScandicCustomFields()
    {
        $object = new MockProduct();
        $object->addCustomField('åäö', 'åäö');
        $markup = $object->toHtml();
        $this->assertEquals(self::stripLineBreaks($markup), '<div class="notranslate" style="display:none">  <span class="nosto_product" style="display:none">    <span class="url">http://my.shop.com/products/test_product.html</span>    <span class="product_id">1</span>    <span class="name">Test Product</span>    <span class="image_url">http://my.shop.com/images/test_product.jpg</span>    <span class="price">99.99</span>    <span class="list_price">110.99</span>    <span class="price_currency_code">USD</span>    <span class="availability">InStock</span>    <span class="categories">      <span class="category">/Mens</span>      <span class="category">/Mens/Shoes</span>    </span>    <span class="description">This is a full description</span>    <span class="brand">Super Brand</span>    <span class="variation_id">USD</span>    <span class="review_count">99</span>    <span class="rating_value">2.5</span>    <span class="alternate_image_urls">      <span class="alternate_image_url">http://shop.com/product_alt.jpg</span>    </span>    <span class="condition">Used</span>    <span class="gtin">gtin</span>    <span class="tags1">      <span class="tag">first</span>    </span>    <span class="tags2">      <span class="tag">second</span>    </span>    <span class="tags3">      <span class="tag">third</span>    </span>    <span class="google_category">All</span>    <span class="skus">    </span>    <span class="variations">    </span>    <span class="custom_fields">      <span class="åäö">åäö</span>    </span>  </span></div>');
    }

    /**
     * Tests that an object with SKUs is serialized to HTML correctly
     */
    public function testObjectWithSkus()
    {
        $object = new MockProductWithSku();
        $markup = $object->toHtml();
        $this->assertEquals(self::stripLineBreaks($markup), '<div class="notranslate" style="display:none">  <span class="nosto_product" style="display:none">    <span class="url">http://my.shop.com/products/test_product.html</span>    <span class="product_id">1</span>    <span class="name">Test Product</span>    <span class="image_url">http://my.shop.com/images/test_product.jpg</span>    <span class="price">99.99</span>    <span class="list_price">110.99</span>    <span class="price_currency_code">USD</span>    <span class="availability">InStock</span>    <span class="categories">      <span class="category">/Mens</span>      <span class="category">/Mens/Shoes</span>    </span>    <span class="description">This is a full description</span>    <span class="brand">Super Brand</span>    <span class="variation_id">USD</span>    <span class="review_count">99</span>    <span class="rating_value">2.5</span>    <span class="alternate_image_urls">      <span class="alternate_image_url">http://shop.com/product_alt.jpg</span>    </span>    <span class="condition">Used</span>    <span class="gtin">gtin</span>    <span class="tags1">      <span class="tag">first</span>    </span>    <span class="tags2">      <span class="tag">second</span>    </span>    <span class="tags3">      <span class="tag">third</span>    </span>    <span class="google_category">All</span>    <span class="skus">      <span class="nosto_sku">        <span class="id">100</span>        <span class="name">Test Product</span>        <span class="price">99.99</span>        <span class="list_price">110.99</span>        <span class="url">http://my.shop.com/products/test_product.html</span>        <span class="image_url">http://my.shop.com/images/test_product.jpg</span>        <span class="gtin">gtin</span>        <span class="availability">InStock</span>      </span>      <span class="nosto_sku">        <span class="id">100</span>        <span class="name">Test Product</span>        <span class="price">99.99</span>        <span class="list_price">110.99</span>        <span class="url">http://my.shop.com/products/test_product.html</span>        <span class="image_url">http://my.shop.com/images/test_product.jpg</span>        <span class="gtin">gtin</span>        <span class="availability">InStock</span>        <span class="custom_fields">          <span class="noSnakeCase">value</span>        </span>      </span>      <span class="nosto_sku">        <span class="id">100</span>        <span class="name">Test Product</span>        <span class="price">99.99</span>        <span class="list_price">110.99</span>        <span class="url">http://my.shop.com/products/test_product.html</span>        <span class="image_url">http://my.shop.com/images/test_product.jpg</span>        <span class="gtin">gtin</span>        <span class="availability">InStock</span>        <span class="custom_fields">          <span class="åäö">åäö</span>        </span>      </span>    </span>    <span class="variations">    </span>  </span></div>');
    }

    /**
     * @param $string
     * @return null|string|string[]
     */
    private static function stripLineBreaks($string)
    {
        return preg_replace("/\r|\n/", "", $string);
    }
}
