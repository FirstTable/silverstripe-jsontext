<?php

/**
 * @package silverstripe-jsontext
 * @subpackage fields
 * @author Russell Michell <russ@theruss.com>
 */

use JSONText\Fields;
use JSONText\Exceptions;

class JSONTextSetValueTest extends SapphireTest
{
    /**
     * @var array
     */
    protected $fixtures = [
        'array'     => 'tests/fixtures/json/array.json',
        'object'    => 'tests/fixtures/json/object.json',
        'invalid'   => 'tests/fixtures/json/invalid.json'
    ];

    /**
     * JSONTextTest constructor.
     * 
     * Modify fixtures property to be able to run on PHP <5.6 without use of constant in class property which 5.6+ allows
     */
    public function __construct()
    {
        foreach($this->fixtures as $name => $path) {
            $this->fixtures[$name] = MODULE_DIR . '/' . $path;
        }
    }

    /**
     * Tests JSONText::setValue() by means of a simple JSONPath expression operating on a JSON array
     */
    public function testSetValueOnSourceArray()
    {
        // Data Source: Array
        // Return Type: ARRAY
        // Expression: '$.[2]' The third item
        $field = JSONText\Fields\JSONText::create('MyJSON');
        $field->setReturnType('array');
        $field->setValue($this->getFixture('array'));
        // Assert current value
        $this->assertEquals(['trabant'], $field->query('$.[2]'));
        // Now update it...
        $field->setValue('lada', null, '$.[2]');
        // Assert new value
        $this->assertEquals(['lada'], $field->query('$.[2]'));

        // Data Source: Array
        // Return Type: ARRAY
        // Expression: '$.[6]' The seventh item
        $field = JSONText\Fields\JSONText::create('MyJSON');
        $field->setReturnType('array');
        $field->setValue($this->getFixture('array'));
        // Assert current value
        $this->assertEquals([33.3333], $field->query('$.[6]'));
        // Now update it...
        $field->setValue(99.99, null, '$.[6]');
        // Assert new value
        $this->assertEquals([99.99], $field->query('$.[6]'));
        
        // Invalid #1
        $this->setExpectedException('\JSONText\Exceptions\JSONTextException');
        $field->setValue(99.99, null, '$[6]'); // Invalid JSON path expression
    }

    /**
     * Tests JSONText::setValue() by means of a simple JSONPath expression operating on a JSON object
     * 
     * Tests performing single and multiple updates
     */
    public function testSetValueOnSourceObject()
    {
        // Data Source: Object
        // Return Type: ARRAY
        // Expression: '$.[2]' The third item
        $field = JSONText\Fields\JSONText::create('MyJSON');
        $field->setReturnType('array');
        $field->setValue($this->getFixture('object'));
        // Assert we cannot use array accessors at the root level of the source JSON _object_
        $this->assertEmpty($field->query('$.[2]'));
        // Assert current types and value
        $this->assertInternalType('array', $field->query('$.cars'));
        $this->assertCount(1, $field->query('$.cars')); // The "cars" key's value is an object returned as a single value array
        $this->assertCount(3, $field->query('$.cars')[0]); //...with three classifications of car manufacturer by country
        $this->assertCount(2, $field->query('$.cars')[0]['british']);
        $this->assertEquals('morris', $field->query('$.cars')[0]['british'][1]);
        
        // Now do a multiple update
        $newCars = [
            'american'  => ['ford', 'tesla'],
            'british'   => ['aston martin', 'austin', 'rover']
        ];

        $field->setValue($newCars, null, '$.cars');
        
        // Assert news types and value
        $this->assertInternalType('array', $field->query('$.cars'));
        $this->assertCount(1, $field->query('$.cars')); // The "cars" key's value is an object returned as a single value array
        $this->assertCount(2, $field->query('$.cars')[0]); //...with three classifications of car manufacturer by country
        $this->assertCount(3, $field->query('$.cars')[0]['british']);
        $this->assertEquals('austin', $field->query('$.cars')[0]['british'][1]);
        
        // So far we've used JSONPath to identify and update, let's try Postgres operators too
        // Now do attempt multiple update
        $newerCars = [
            'american'   => ['chrysler', 'general motors', 'edsel']
        ];

        $this->setExpectedException('\JSONText\Exceptions\JSONTextException');
        $field->setValue($newerCars, null, '{"cars":"american"}'); // setValue() only takes JSONPath expressions
    }
    
    /**
     * Get the contents of a fixture
     * 
     * @param string $fixture
     * @return string
     */
    private function getFixture($fixture)
    {
        $files = $this->fixtures;
        return file_get_contents($files[$fixture]);
    }

}