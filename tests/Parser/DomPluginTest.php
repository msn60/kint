<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Jonathan Vollebregt (jnvsor@gmail.com), Rokas Šleinius (raveren@gmail.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Kint\Test\Parser;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;
use Dom\Text;
use Dom\XMLDocument;
use Kint\Parser\AbstractPlugin;
use Kint\Parser\ClassMethodsPlugin;
use Kint\Parser\ClassStaticsPlugin;
use Kint\Parser\DomPlugin;
use Kint\Parser\IteratorPlugin;
use Kint\Parser\Parser;
use Kint\Test\KintTestCase;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\DomNodeListValue;
use Kint\Value\DomNodeValue;
use Kint\Value\Representation\Representation;
use Kint\Value\StringValue;
use ReflectionClass;

/**
 * @coversNothing
 */
class DomPluginTest extends KintTestCase
{
    public const TEST_XML = <<<END
        <?xml version="1.0" encoding="UTF-8"?>
        <x viewBox="0 0 30 150">
            <g stroke-width="2" fill="#FFF">
                <inner>Text</inner>
            </g>
            <g />
            String value
            <wrap>
                <wrap><text>String element</text></wrap>
                <not-php-compatible also-not="php-compatible" />
                <value type="string">Contents</value>
            </wrap>
            <both attribs="exist"><![CDATA[And string]]></both>
        </x>
        END;

    public const TEST_XML_NS = <<<END
        <?xml version="1.0" encoding="UTF-8"?>
        <x xmlns="http://localhost/" xmlns:test="http://localhost/test" viewBox="0 0 30 150">
            <test:g stroke-width="2" fill="#FFF">
                <inner>Text</inner>
            </test:g>
            <g xmlns="http://localhost/test" />
            String value
            <both attribs="base" test:attribs="exists"><![CDATA[And string]]></both>
        </x>
        END;

    public const TEST_HTML = <<<END
        <!DOCTYPE html>
        <body>
            <strong class="text">Hello world</strong>
            <DIV no:namespaces="allowed" />
        </body>
        END;

    /**
     * @covers \Kint\Parser\DomPlugin::__construct
     * @covers \Kint\Parser\DomPlugin::setParser
     */
    public function testConstruct()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $p = new Parser();
        $d = new DomPlugin($p);

        $reflector = new ReflectionClass($d);
        $mprop = $reflector->getProperty('methods_plugin');
        $mprop->setAccessible(true);
        $m = $mprop->getValue($d);
        $this->assertInstanceOf(ClassMethodsPlugin::class, $m);
        $sprop = $reflector->getProperty('statics_plugin');
        $sprop->setAccessible(true);
        $s = $sprop->getValue($d);
        $this->assertInstanceOf(ClassStaticsPlugin::class, $s);

        $reflector = new ReflectionClass(AbstractPlugin::class);
        $aparser = $reflector->getProperty('parser');
        $aparser->setAccessible(true);

        $p = new Parser();
        $this->assertNotSame($p, $aparser->getValue($m));
        $this->assertNotSame($p, $aparser->getValue($s));
        $d->setParser($p);
        $this->assertSame($p, $aparser->getValue($m));
        $this->assertSame($p, $aparser->getValue($s));
    }

    public function testPropConsts()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $writable_props = ['id', 'className', 'innerHTML', 'substitutedNodeValue', 'textContent'];

        $props = [];
        $r = new ReflectionClass(Node::class);
        foreach ($r->getProperties() as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $props[$prop->name] = !\in_array($prop->name, $writable_props, true);
        }

        $this->assertSame($props, DomPlugin::NODE_PROPS);

        $element_props = [];
        $r = new ReflectionClass(Element::class);
        foreach ($r->getProperties() as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $element_props[$prop->name] = !\in_array($prop->name, $writable_props, true);
        }

        $this->assertSame($element_props, DomPlugin::NODE_PROPS + DomPlugin::ELEMENT_PROPS);
    }

    /**
     * @covers \Kint\Parser\DomPlugin::parse
     * @covers \Kint\Parser\DomPlugin::parseText
     * @covers \Kint\Parser\DomPlugin::parseList
     * @covers \Kint\Parser\DomPlugin::parseNode
     * @covers \Kint\Parser\DomPlugin::parseProperty
     * @covers \Kint\Parser\DomPlugin::getChildrenRepresentation
     */
    public function testParseXML()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DomPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = XMLDocument::createFromString(self::TEST_XML);
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        DomPlugin::$verbose = false;
        $o = $p->parse($v, clone $b);

        $this->basicAssertionsXml($o, false);
    }

    /**
     * @covers \Kint\Parser\DomPlugin::parse
     * @covers \Kint\Parser\DomPlugin::parseText
     * @covers \Kint\Parser\DomPlugin::parseList
     * @covers \Kint\Parser\DomPlugin::parseNode
     * @covers \Kint\Parser\DomPlugin::parseProperty
     * @covers \Kint\Parser\DomPlugin::getChildrenRepresentation
     */
    public function testParseXMLVerbose()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DomPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = XMLDocument::createFromString(self::TEST_XML);
        $b = new BaseContext('$v');
        $b->access_path = '$v';
        DomPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $this->basicAssertionsXml($o, true);

        $found_props = [];
        foreach ($o->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertCount(1, $found_props['childNodes']->getRepresentation('iterator')->contents);
        $this->assertTrue($found_props['firstChild']->hasHint('blacklist'));
        $this->assertSame(Element::class, $found_props['firstChild']->getClassName());
        $this->assertTrue($found_props['lastChild']->hasHint('blacklist'));
        $this->assertSame(Element::class, $found_props['lastChild']->getClassName());

        $root_node = \reset($found_props['childNodes']->getRepresentation('iterator')->contents);

        $root_props = [];
        foreach ($root_node->getChildren() as $val) {
            $root_props[$val->getContext()->getName()] = $val;
        }

        $this->assertTrue($root_props['ownerDocument']->hasHint('blacklist'));
        $this->assertSame(XMLDocument::class, $root_props['ownerDocument']->getClassName());
        $this->assertTrue($root_props['parentNode']->hasHint('blacklist'));
        $this->assertSame(XMLDocument::class, $root_props['parentNode']->getClassName());
        $this->assertTrue($root_props['firstElementChild']->hasHint('blacklist'));
        $this->assertSame(Element::class, $root_props['firstElementChild']->getClassName());
        $this->assertTrue($root_props['lastElementChild']->hasHint('blacklist'));
        $this->assertSame(Element::class, $root_props['lastElementChild']->getClassName());
        $this->assertTrue($root_props['firstChild']->hasHint('blacklist'));
        $this->assertSame(Text::class, $root_props['firstChild']->getClassName());
        $this->assertTrue($root_props['lastChild']->hasHint('blacklist'));
        $this->assertSame(Text::class, $root_props['lastChild']->getClassName());
    }

    /**
     * @covers \Kint\Parser\DomPlugin::parse
     * @covers \Kint\Parser\DomPlugin::parseText
     * @covers \Kint\Parser\DomPlugin::parseList
     * @covers \Kint\Parser\DomPlugin::parseNode
     * @covers \Kint\Parser\DomPlugin::parseProperty
     * @covers \Kint\Parser\DomPlugin::getChildrenRepresentation
     */
    public function testNamespaces()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DomPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = XMLDocument::createFromString(self::TEST_XML_NS);
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        DomPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $root = $o->getRepresentation('children')->contents[0];
        $this->assertSame('$v->childNodes[0]', $root->getContext()->getAccessPath());

        $root_props = [];
        foreach ($root->getChildren() as $val) {
            $root_props[$val->getContext()->getName()] = $val;
        }

        $this->assertSame('x', $root_props['tagName']->getValue());
        $this->assertSame('x', $root_props['localName']->getValue());
        $this->assertSame('http://localhost/', $root_props['namespaceURI']->getValue());
        $this->assertSame('$v->childNodes[0]->namespaceURI', $root_props['namespaceURI']->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[0]->attributes', $root_props['attributes']->getContext()->getAccessPath());
        $this->assertSame('3', $root_props['attributes']->getDisplaySize());

        $this->assertEquals(
            $root_props['attributes']->getRepresentation('iterator')->contents,
            $root->getRepresentation('attributes')->contents
        );

        $attribs = [];
        foreach ($root->getRepresentation('attributes')->contents as $val) {
            $attribs[$val->getContext()->getName()] = $val;
        }

        $this->assertCount(3, $attribs);

        $this->assertSame('http://localhost/', $attribs['xmlns']->getValue());
        $this->assertSame('$v->childNodes[0]->attributes[\'xmlns\']->nodeValue', $attribs['xmlns']->getContext()->getAccessPath());

        $this->assertSame('http://localhost/test', $attribs['xmlns:test']->getValue());
        $this->assertSame('$v->childNodes[0]->attributes[\'xmlns:test\']->nodeValue', $attribs['xmlns:test']->getContext()->getAccessPath());

        $this->assertSame('0 0 30 150', $attribs['viewBox']->getValue());
        $this->assertSame('$v->childNodes[0]->attributes[\'viewBox\']->nodeValue', $attribs['viewBox']->getContext()->getAccessPath());

        $g = $root->getRepresentation('children')->contents[0];
        $this->assertSame('test:g', $g->getDisplayName());
        $this->assertSame('$v->childNodes[0]->childNodes[1]', $g->getContext()->getAccessPath());

        $gprops = [];
        foreach ($g->getChildren() as $val) {
            $gprops[$val->getContext()->getName()] = $val;
        }

        $this->assertSame('test:g', $gprops['tagName']->getValue());
        $this->assertSame('g', $gprops['localName']->getValue());
        $this->assertSame('http://localhost/test', $gprops['namespaceURI']->getValue());
        $this->assertSame('$v->childNodes[0]->childNodes[1]->namespaceURI', $gprops['namespaceURI']->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[0]->childNodes[1]->attributes', $gprops['attributes']->getContext()->getAccessPath());
        $this->assertSame('2', $gprops['attributes']->getDisplaySize());

        $g = $root->getRepresentation('children')->contents[1];
        $this->assertSame('g', $g->getDisplayName());
        $this->assertSame('$v->childNodes[0]->childNodes[3]', $g->getContext()->getAccessPath());

        $gprops = [];
        foreach ($g->getChildren() as $val) {
            $gprops[$val->getContext()->getName()] = $val;
        }

        $this->assertSame('g', $gprops['tagName']->getValue());
        $this->assertSame('g', $gprops['localName']->getValue());
        $this->assertSame('http://localhost/test', $gprops['namespaceURI']->getValue());
        $this->assertSame('$v->childNodes[0]->childNodes[3]->namespaceURI', $gprops['namespaceURI']->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[0]->childNodes[3]->attributes', $gprops['attributes']->getContext()->getAccessPath());
        $this->assertSame('1', $gprops['attributes']->getDisplaySize());

        $both = $root->getRepresentation('children')->contents[3];
        $this->assertSame('both', $both->getDisplayName());
        $this->assertSame('$v->childNodes[0]->childNodes[5]', $both->getContext()->getAccessPath());

        $attribs = [];
        foreach ($both->getRepresentation('attributes')->contents as $val) {
            $attribs[$val->getContext()->getName()] = $val;
        }

        $this->assertCount(2, $attribs);

        $this->assertSame('base', $attribs['attribs']->getValue());
        $this->assertSame('$v->childNodes[0]->childNodes[5]->attributes[\'attribs\']->nodeValue', $attribs['attribs']->getContext()->getAccessPath());

        $this->assertSame('exists', $attribs['test:attribs']->getValue());
        $this->assertSame('$v->childNodes[0]->childNodes[5]->attributes[\'test:attribs\']->nodeValue', $attribs['test:attribs']->getContext()->getAccessPath());
    }

    /**
     * @covers \Kint\Parser\DomPlugin::parse
     * @covers \Kint\Parser\DomPlugin::parseText
     * @covers \Kint\Parser\DomPlugin::parseList
     * @covers \Kint\Parser\DomPlugin::parseNode
     * @covers \Kint\Parser\DomPlugin::parseProperty
     * @covers \Kint\Parser\DomPlugin::getChildrenRepresentation
     */
    public function testDepthLimit()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DomPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = XMLDocument::createFromString(self::TEST_XML);
        $b = new BaseContext('$v');
        $b->access_path = '$v';
        DomPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $o_props = [];
        foreach ($o->getChildren() as $val) {
            $o_props[$val->getContext()->getName()] = $val;
        }

        $x = $o->getRepresentation('children')->contents[0];
        $this->assertSame('x', $x->getDisplayName());
        $this->assertNull($x->getDisplaySize());
        $this->assertSame(\count($x->getChildren()), \count($x->getRepresentation('properties')->contents));
        $this->assertCount(5, $x->getRepresentation('children')->contents);
        $this->assertFalse($x->hasHint('depth_limit'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('constants'));

        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertSame('g', $g1->getDisplayName());
        $this->assertNull($g1->getDisplaySize());
        $this->assertSame(\count($g1->getChildren()), \count($g1->getRepresentation('properties')->contents));
        $this->assertCount(1, $g1->getRepresentation('children')->contents);
        $this->assertFalse($g1->hasHint('depth_limit'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('constants'));

        $found_props = [];
        foreach ($g1->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertFalse($found_props['childNodes']->hasHint('depth_limit'));

        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertSame('g', $g2->getDisplayName());
        $this->assertNull($g2->getDisplaySize());
        $this->assertSame(\count($g2->getChildren()), \count($g2->getRepresentation('properties')->contents));
        $this->assertNull($g2->getRepresentation('children'));
        $this->assertFalse($g2->hasHint('depth_limit'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('constants'));

        $text = $x->getRepresentation('children')->contents[2];
        $this->assertInstanceOf(StringValue::class, $text);
        $this->assertSame('#text', $text->getDisplayName());
        $this->assertSame(22, $text->getLength());
        $this->assertFalse($text->hasHint('depth_limit'));
        $this->assertNotNull($text->getValue());
        $this->assertNull($text->getRepresentation('properties'));
        $this->assertNull($text->getRepresentation('methods'));
        $this->assertNull($text->getRepresentation('constants'));

        $wrap = $x->getRepresentation('children')->contents[3];
        $this->assertSame('wrap', $wrap->getDisplayName());
        $this->assertNull($wrap->getDisplaySize());
        $this->assertSame(\count($wrap->getChildren()), \count($wrap->getRepresentation('properties')->contents));
        $this->assertCount(3, $wrap->getRepresentation('children')->contents);
        $this->assertFalse($wrap->hasHint('depth_limit'));
        $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('constants'));

        $p->setDepthLimit(4);
        $o = $p->parse($v, clone $b);

        $x = $o->getRepresentation('children')->contents[0];
        $this->assertSame('x', $x->getDisplayName());
        $this->assertNull($x->getDisplaySize());
        $this->assertSame(\count($x->getChildren()), \count($x->getRepresentation('properties')->contents));
        $this->assertCount(5, $x->getRepresentation('children')->contents);
        $this->assertFalse($x->hasHint('depth_limit'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('constants'));

        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertSame('g', $g1->getDisplayName());
        $this->assertNull($g1->getDisplaySize());
        $this->assertTrue($g1->hasHint('depth_limit'));
        $this->assertNull($g1->getRepresentation('children'));
        $this->assertNull($g1->getRepresentation('properties'));
        $this->assertNull($g1->getRepresentation('attributes'));
        $this->assertNull($g1->getRepresentation('constants'));

        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertSame('g', $g2->getDisplayName());
        $this->assertNull($g2->getDisplaySize());
        $this->assertTrue($g2->hasHint('depth_limit'));
        $this->assertNull($g2->getRepresentation('children'));
        $this->assertNull($g2->getRepresentation('properties'));
        $this->assertNull($g2->getRepresentation('attributes'));
        $this->assertNull($g2->getRepresentation('constants'));

        $text = $x->getRepresentation('children')->contents[2];
        $this->assertInstanceOf(StringValue::class, $text);
        $this->assertSame('#text', $text->getDisplayName());
        $this->assertSame(22, $text->getLength());
        $this->assertFalse($text->hasHint('depth_limit'));
        $this->assertNotNull($text->getValue());
        $this->assertNull($text->getRepresentation('children'));
        $this->assertNull($text->getRepresentation('properties'));
        $this->assertNull($text->getRepresentation('attributes'));
        $this->assertNull($text->getRepresentation('constants'));

        $wrap = $x->getRepresentation('children')->contents[3];
        $this->assertSame('wrap', $wrap->getDisplayName());
        $this->assertNull($wrap->getDisplaySize());
        $this->assertTrue($wrap->hasHint('depth_limit'));
        $this->assertNull($wrap->getRepresentation('children'));
        $this->assertNull($wrap->getRepresentation('properties'));
        $this->assertNull($wrap->getRepresentation('attributes'));
        $this->assertNull($wrap->getRepresentation('constants'));

        $p->setDepthLimit(5);
        $o = $p->parse($v, clone $b);

        $x = $o->getRepresentation('children')->contents[0];
        $this->assertSame('x', $x->getDisplayName());
        $this->assertNull($x->getDisplaySize());
        $this->assertSame(\count($x->getChildren()), \count($x->getRepresentation('properties')->contents));
        $this->assertCount(5, $x->getRepresentation('children')->contents);
        $this->assertFalse($x->hasHint('depth_limit'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('constants'));

        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertSame('g', $g1->getDisplayName());
        $this->assertNull($g1->getDisplaySize());
        $this->assertSame(\count($g1->getChildren()), \count($g1->getRepresentation('properties')->contents));
        $this->assertCount(1, $g1->getRepresentation('children')->contents);
        $this->assertFalse($g1->hasHint('depth_limit'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('constants'));

        $found_props = [];
        foreach ($g1->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertTrue($found_props['childNodes']->hasHint('depth_limit'));
        $this->assertFalse($found_props['attributes']->hasHint('depth_limit'));

        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertSame('g', $g2->getDisplayName());
        $this->assertNull($g2->getDisplaySize());
        $this->assertSame(\count($g2->getChildren()), \count($g2->getRepresentation('properties')->contents));
        $this->assertFalse($g2->hasHint('depth_limit'));
        $this->assertNull($wrap->getRepresentation('children'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('constants'));
    }

    /**
     * @covers \Kint\Parser\DomPlugin::parse
     * @covers \Kint\Parser\DomPlugin::parseText
     * @covers \Kint\Parser\DomPlugin::parseList
     * @covers \Kint\Parser\DomPlugin::parseNode
     * @covers \Kint\Parser\DomPlugin::parseProperty
     * @covers \Kint\Parser\DomPlugin::getChildrenRepresentation
     */
    public function testHTML()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DomPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = HTMLDocument::createFromString(self::TEST_HTML);
        $b = new BaseContext('$v');
        $b->access_path = '$v';
        DomPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $expected_props = [
            'NODE_PROPS' => \count(DomPlugin::NODE_PROPS),
            'ELEMENT_PROPS' => \count(DomPlugin::ELEMENT_PROPS + DomPlugin::NODE_PROPS),
        ];

        $found_props = [];
        foreach ($o->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayNotHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(0, $o->getContext()->getDepth());
        $this->assertNull($o->getDisplaySize());
        $this->assertCount(2, $o->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertTrue($found_props['childNodes']->getContext()->readonly);
        $this->assertSame('2', $found_props['childNodes']->getDisplaySize());
        $this->assertCount(2, $found_props['childNodes']->getChildren());
        $this->assertCount(2, $found_props['childNodes']->getRepresentation('iterator')->contents);
        $this->assertTrue($found_props['childNodes']->hasHint('omit_spl_id'));
        $this->assertNull($o->getRepresentation('attributes'));

        $this->assertInstanceOf(Representation::class, $o->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $o->getRepresentation('methods'));
        $this->assertCount(\count($found_props), $o->getRepresentation('properties')->contents);
        $this->assertCount($expected_props['NODE_PROPS'], $found_props);
        $this->assertTrue($found_props['nodeType']->getContext()->readonly);
        $this->assertTrue($found_props['textContent']->getContext()->readonly);

        // html
        $html = $found_props['childNodes']->getRepresentation('iterator')->contents[1] ?? null;

        $this->assertNotNull($html);
        $this->assertSame('$v->childNodes[1]', $html->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[1]', $o->getRepresentation('children')->contents[1]->getContext()->getAccessPath());

        $found_props = [];
        foreach ($html->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(2, $html->getContext()->getDepth());
        $this->assertNull($o->getDisplaySize());
        $this->assertCount(2, $html->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertTrue($found_props['childNodes']->getContext()->readonly);
        $this->assertSame('2', $found_props['childNodes']->getDisplaySize());
        $this->assertCount(2, $found_props['childNodes']->getChildren());
        $this->assertCount(2, $found_props['childNodes']->getRepresentation('iterator')->contents);
        $this->assertTrue($found_props['childNodes']->hasHint('omit_spl_id'));

        $this->assertInstanceOf(Representation::class, $html->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $html->getRepresentation('methods'));
        $this->assertCount(\count($found_props), $html->getRepresentation('properties')->contents);
        $this->assertCount($expected_props['ELEMENT_PROPS'], $found_props);
        $this->assertTrue($found_props['nodeType']->getContext()->readonly);
        $this->assertFalse($found_props['textContent']->getContext()->readonly);

        // head
        $head = $html->getRepresentation('children')->contents[0];
        $this->assertSame('head', $head->getDisplayName());
        $this->assertSame('$v->childNodes[1]->childNodes[0]', $head->getContext()->getAccessPath());

        // body
        $body = $html->getRepresentation('children')->contents[1];
        $this->assertNotNull($body);
        $this->assertSame('$v->childNodes[1]->childNodes[1]', $body->getContext()->getAccessPath());

        $found_props = [];
        foreach ($body->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame('BODY', $found_props['nodeName']->getValue());
        $this->assertSame('body', $found_props['localName']->getValue());
        $this->assertSame('BODY', $found_props['tagName']->getValue());

        // strong
        $strong = $body->getRepresentation('children')->contents[0];
        $this->assertNotNull($strong);
        $this->assertSame('$v->childNodes[1]->childNodes[1]->childNodes[1]', $strong->getContext()->getAccessPath());

        $found_props = [];
        foreach ($strong->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame('STRONG', $found_props['nodeName']->getValue());
        $this->assertSame('strong', $found_props['localName']->getValue());
        $this->assertSame('STRONG', $found_props['tagName']->getValue());

        $attributes = $strong->getRepresentation('attributes');
        $this->assertInstanceOf(Representation::class, $attributes);

        $attrib = $attributes->contents[0];
        $this->assertInstanceOf(StringValue::class, $attrib);
        $this->assertSame('class', $attrib->getDisplayName());
        $this->assertSame('$v->childNodes[1]->childNodes[1]->childNodes[1]->attributes[\'class\']->nodeValue', $attrib->getContext()->getAccessPath());
        $this->assertSame('text', $attrib->getValue());

        $this->assertSame('text', $found_props['className']->getValue());
        $this->assertSame('text', $found_props['classList']->getRepresentation('iterator')->contents[0]->getValue());

        // div
        $div = $body->getRepresentation('children')->contents[1];
        $this->assertNotNull($div);
        $this->assertSame('$v->childNodes[1]->childNodes[1]->childNodes[3]', $div->getContext()->getAccessPath());

        $found_props = [];
        foreach ($div->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame('DIV', $found_props['nodeName']->getValue());
        $this->assertSame('div', $found_props['localName']->getValue());
        $this->assertSame('DIV', $found_props['tagName']->getValue());

        $attributes = $div->getRepresentation('attributes');
        $this->assertInstanceOf(Representation::class, $attributes);

        $attrib = $attributes->contents[0];
        $this->assertInstanceOf(StringValue::class, $attrib);
        $this->assertSame('no:namespaces', $attrib->getDisplayName());
        $this->assertSame('$v->childNodes[1]->childNodes[1]->childNodes[3]->attributes[\'no:namespaces\']->nodeValue', $attrib->getContext()->getAccessPath());
        $this->assertSame('allowed', $attrib->getValue());
    }

    /**
     * @covers \Kint\Parser\DomPlugin::getTriggers
     * @covers \Kint\Parser\DomPlugin::getTypes
     */
    public function testHooks()
    {
        $p = new DomPlugin($this->createStub(Parser::class));

        $this->assertSame(['object'], $p->getTypes());
        $this->assertSame(KINT_PHP84 ? Parser::TRIGGER_BEGIN : Parser::TRIGGER_NONE, $p->getTriggers());
    }

    protected function basicAssertionsXml(AbstractValue $o, bool $verbose)
    {
        $expected_props = [
            'NODE_PROPS' => \count(DomPlugin::NODE_PROPS),
            'ELEMENT_PROPS' => \count(DomPlugin::ELEMENT_PROPS + DomPlugin::NODE_PROPS),
        ];

        $found_props = [];
        foreach ($o->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayNotHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertInstanceOf(DomNodeValue::class, $o);
        $this->assertSame(0, $o->getContext()->getDepth());
        $this->assertFalse($o->hasHint('omit_spl_id'));
        $this->assertNull($o->getDisplaySize());
        $this->assertCount(1, $o->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertInstanceOf(DomNodeListValue::class, $found_props['childNodes']);
        $this->assertTrue($found_props['childNodes']->getContext()->readonly);
        $this->assertSame('1', $found_props['childNodes']->getDisplaySize());
        $this->assertCount(1, $found_props['childNodes']->getChildren());
        $this->assertCount(1, $found_props['childNodes']->getRepresentation('iterator')->contents);
        $this->assertTrue($found_props['childNodes']->hasHint('omit_spl_id'));
        $this->assertNull($o->getRepresentation('attributes'));

        if ($verbose) {
            $this->assertInstanceOf(Representation::class, $o->getRepresentation('properties'));
            $this->assertInstanceOf(Representation::class, $o->getRepresentation('methods'));
            $this->assertInstanceOf(Representation::class, $o->getRepresentation('constants'));
            $this->assertCount(\count($found_props), $o->getRepresentation('properties')->contents);
            $this->assertCount($expected_props['NODE_PROPS'], $found_props);
            $this->assertTrue($found_props['nodeType']->getContext()->readonly);
            $this->assertTrue($found_props['textContent']->getContext()->readonly);
        } else {
            $this->assertNull($o->getRepresentation('properties'));
            $this->assertNull($o->getRepresentation('methods'));
            $this->assertNull($o->getRepresentation('constants'));
            $this->assertCount(2, $found_props);
        }

        // x
        $x = $found_props['childNodes']->getRepresentation('iterator')->contents[0] ?? null;

        $this->assertNotNull($x);
        $this->assertSame('$v->childNodes[0]', $x->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[0]', $o->getRepresentation('children')->contents[0]->getContext()->getAccessPath());

        $found_props = [];
        foreach ($x->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertInstanceOf(DomNodeValue::class, $x);
        $this->assertSame(2, $x->getContext()->getDepth());
        $this->assertTrue($x->hasHint('omit_spl_id'));
        $this->assertNull($x->getDisplaySize());
        $this->assertCount(5, $x->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertInstanceOf(DomNodeListValue::class, $found_props['childNodes']);
        $this->assertTrue($found_props['childNodes']->getContext()->readonly);
        $this->assertSame('9', $found_props['childNodes']->getDisplaySize());
        $this->assertCount(9, $found_props['childNodes']->getChildren());
        $this->assertCount(9, $found_props['childNodes']->getRepresentation('iterator')->contents);
        $this->assertTrue($found_props['childNodes']->hasHint('omit_spl_id'));

        if ($verbose) {
            $this->assertInstanceOf(Representation::class, $x->getRepresentation('properties'));
            $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
            $this->assertInstanceOf(Representation::class, $o->getRepresentation('constants'));
            $this->assertCount(\count($found_props), $x->getRepresentation('properties')->contents);
            $this->assertCount($expected_props['ELEMENT_PROPS'], $found_props);
            $this->assertTrue($found_props['nodeType']->getContext()->readonly);
            $this->assertFalse($found_props['textContent']->getContext()->readonly);
        } else {
            $this->assertNull($x->getRepresentation('properties'));
            $this->assertNull($x->getRepresentation('methods'));
            $this->assertNull($x->getRepresentation('constants'));
            $this->assertCount(3, $found_props);
        }

        // x attributes
        $attributes = $x->getRepresentation('attributes');
        $this->assertInstanceOf(Representation::class, $attributes);

        $attrib = $attributes->contents[0];
        $this->assertInstanceOf(StringValue::class, $attrib);
        $this->assertSame('viewBox', $attrib->getDisplayName());
        $this->assertSame('$v->childNodes[0]->attributes[\'viewBox\']->nodeValue', $attrib->getContext()->getAccessPath());
        $this->assertSame('0 0 30 150', $attrib->getValue());

        // g1
        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertNotNull($g1);
        $this->assertSame('$v->childNodes[0]->childNodes[1]', $g1->getContext()->getAccessPath());

        $found_props = [];
        foreach ($g1->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertInstanceOf(DomNodeValue::class, $g1);
        $this->assertSame(4, $g1->getContext()->getDepth());
        $this->assertTrue($g1->hasHint('omit_spl_id'));
        $this->assertCount(1, $g1->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertInstanceOf(DomNodeListValue::class, $found_props['childNodes']);
        $this->assertTrue($found_props['childNodes']->getContext()->readonly);
        $this->assertSame('3', $found_props['childNodes']->getDisplaySize());
        $this->assertCount(3, $found_props['childNodes']->getChildren());
        $this->assertCount(3, $found_props['childNodes']->getRepresentation('iterator')->contents);
        $this->assertTrue($found_props['childNodes']->hasHint('omit_spl_id'));

        if ($verbose) {
            $this->assertInstanceOf(Representation::class, $g1->getRepresentation('properties'));
            $this->assertInstanceOf(Representation::class, $g1->getRepresentation('methods'));
            $this->assertInstanceOf(Representation::class, $g1->getRepresentation('constants'));
            $this->assertCount(\count($found_props), $g1->getRepresentation('properties')->contents);
            $this->assertCount($expected_props['ELEMENT_PROPS'], $found_props);
            $this->assertTrue($found_props['nodeType']->getContext()->readonly);
            $this->assertFalse($found_props['textContent']->getContext()->readonly);
        } else {
            $this->assertNull($g1->getRepresentation('properties'));
            $this->assertNull($g1->getRepresentation('methods'));
            $this->assertNull($g1->getRepresentation('constants'));
            $this->assertCount(3, $found_props);
        }

        // g2
        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertNotNull($g2);
        $this->assertSame('$v->childNodes[0]->childNodes[3]', $g2->getContext()->getAccessPath());

        $found_props = [];
        foreach ($g2->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertInstanceOf(DomNodeValue::class, $g1);
        $this->assertSame(4, $g2->getContext()->getDepth());
        $this->assertTrue($g2->hasHint('omit_spl_id'));
        $this->assertNull($g2->getRepresentation('children')); // Children with empty space removed
        $this->assertInstanceOf(DomNodeListValue::class, $found_props['childNodes']);
        $this->assertTrue($found_props['childNodes']->getContext()->readonly);
        $this->assertSame('0', $found_props['childNodes']->getDisplaySize());
        $this->assertCount(0, $found_props['childNodes']->getChildren());
        $this->assertNull($found_props['childNodes']->getRepresentation('iterator'));
        $this->assertTrue($found_props['childNodes']->hasHint('omit_spl_id'));

        if ($verbose) {
            $this->assertInstanceOf(Representation::class, $g2->getRepresentation('properties'));
            $this->assertInstanceOf(Representation::class, $g2->getRepresentation('methods'));
            $this->assertInstanceOf(Representation::class, $g2->getRepresentation('constants'));
            $this->assertCount(\count($found_props), $g2->getRepresentation('properties')->contents);
            $this->assertCount($expected_props['ELEMENT_PROPS'], $found_props);
            $this->assertTrue($found_props['nodeType']->getContext()->readonly);
            $this->assertFalse($found_props['textContent']->getContext()->readonly);
        } else {
            $this->assertNull($g2->getRepresentation('properties'));
            $this->assertNull($g2->getRepresentation('methods'));
            $this->assertNull($g2->getRepresentation('constants'));
            $this->assertCount(3, $found_props);
        }

        // not-php-compatible
        $incomp = $x->getRepresentation('children')->contents[3]->getRepresentation('children')->contents[1];

        $this->assertSame('$v->childNodes[0]->childNodes[5]->childNodes[3]', $incomp->getContext()->getAccessPath());
        $this->assertSame(
            '$v->childNodes[0]->childNodes[5]->childNodes[3]->attributes[\'also-not\']->nodeValue',
            $incomp->getRepresentation('attributes')->contents[0]->getContext()->getAccessPath()
        );
        $this->assertSame('php-compatible', $incomp->getRepresentation('attributes')->contents[0]->getValue());
    }
}
