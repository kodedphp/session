<?php

namespace Koded\Session;

trait SessionTestCaseTrait
{
    /**
     * @var Session
     */
    protected $SUT;

    public function test_constructor()
    {
        $this->assertFalse($this->SUT->accessed());
        $this->assertFalse($this->SUT->modified());
        $this->assertNotEmpty($this->SUT->token());
    }

    public function test_get()
    {
        $this->assertSame('bar', $this->SUT->get('foo'));
        $this->assertTrue($this->SUT->accessed());
        $this->assertNull($this->SUT->fubar, 'Non-existing keys returns NULL');
    }

    public function test_set()
    {
        $this->SUT->set('qux', 'zim');
        $this->assertSame('zim', $this->SUT->qux);
        $this->assertTrue($this->SUT->modified());

        $_SESSION['qux'] = 'shmux';
        $this->assertSame('shmux', $this->SUT->get('qux'));
    }

    public function test_add()
    {
        $this->SUT->add('foo', 42);
        $this->assertSame('bar', $this->SUT->foo, 'With add() the value is not replaced if already set');
        $this->assertFalse($this->SUT->modified());
    }

    public function test_remove()
    {
        // Ensure 2 items
        $this->SUT->replace(['foo' => 'bar', 'qux' => 'zim']);
        $this->assertEquals(2, $this->SUT->count(), 'Expecting 2 items in the session');

        $this->SUT->remove('foo');
        $this->assertEquals(1, $this->SUT->count(), 'Should be 1 item in the session');
        $this->assertFalse($this->SUT->has('foo'), 'Only qux should be set');
        $this->assertTrue($this->SUT->has('qux'));
        $this->assertTrue($this->SUT->modified());
    }

    public function test_all()
    {
        $data = $this->SUT->all();

        $this->assertArrayHasKey('foo', $data);
        $this->assertArrayHasKey(Session::STAMP, $data);
        $this->assertArrayHasKey(Session::AGENT, $data);
        $this->assertArrayHasKey(Session::TOKEN, $data);
        $this->assertTrue($this->SUT->accessed());
    }

    public function test_has()
    {
        $this->assertTrue($this->SUT->has('foo'));
        $this->assertFalse($this->SUT->has('this_is_not_set'));
    }

    public function test_toData()
    {
        $data = $this->SUT->toData();

        $this->assertEquals('bar', $data->foo);
        $this->assertNull($data->get('_stamp'));
        $this->assertNull($data->get('_agent'));
        $this->assertNull($data->get('_token'));
    }

    /*
     *
     * (mutator methods)
     *
     */

    public function test_clear()
    {
        $this->assertEquals(['qux' => 'zim', 'foo' => 'bar'], $this->SUT->toArray());

        $this->SUT->clear();
        $this->assertEmpty($this->SUT->toArray());
        $this->assertTrue($this->SUT->modified());
    }

    public function test_destroy()
    {
        $sessionId = $this->SUT->id();

        $this->assertTrue($this->SUT->destroy());
        $this->assertNotEquals($sessionId, $this->SUT->id(), 'Session id is regenerated');
    }

    public function test_regenerate()
    {
        $sessionId = $this->SUT->id();

        $this->assertTrue($this->SUT->regenerate());
        $this->assertNotEquals($this->SUT->id(), $sessionId);
    }

    public function test_flash()
    {
        $this->assertNull($this->SUT->flash('foo'));
        $this->assertTrue($this->SUT->modified());

        // Set flash data
        $this->SUT->flash('foo', '123');
        $this->assertArrayHasKey(Session::FLASH, $_SESSION);

        // Get flash data
        $this->assertSame(['foo' => '123'], $this->SUT->flash('foo'), 'After flash, the key is unset');
        $this->assertArrayNotHasKey(Session::FLASH, $_SESSION);
    }

    public function test_replace()
    {
        $newData = ['name' => 'value'];
        $oldData = $this->SUT->replace($newData);

        $this->assertSame($newData, $this->SUT->toArray(), 'The session data is replaced');
        $this->assertArraySubset(['foo' => 'bar'], $oldData);
        $this->assertTrue($this->SUT->modified());
    }

    public function test_import_ignores_non_string_keys()
    {
        $oldData = $this->SUT->toArray();
        $newData = [1 => 2];

        $this->SUT->import($newData);

        $this->assertSame($oldData, $this->SUT->toArray(), 'The non-string keys are ignored');
        $this->assertArraySubset(['foo' => 'bar'], $oldData);
        $this->assertTrue($this->SUT->modified());
    }

    public function test_import_should_import()
    {
        $this->SUT->import(['name' => 'changed']);

        $this->assertSame([
            'name' => 'changed',
            'foo'  => 'bar'
        ], $this->SUT->toArray(), 'The existing session variables are replaced');
    }

    /*
     *
     * (support methods)
     *
     */

    public function test_id()
    {
        $this->assertNotEmpty($this->SUT->id());
    }

    public function test_accessed()
    {
        $this->assertFalse($this->SUT->accessed());
        $value = $this->SUT->foo;
        $this->assertTrue($this->SUT->accessed());
        $this->assertSame('bar', $value);
    }

    public function test_modified()
    {
        $this->assertFalse($this->SUT->modified());

        $this->SUT->foo = 'zim';
        $this->assertTrue($this->SUT->modified());
        $this->assertFalse($this->SUT->accessed(), 'This method  does not flag the session accessed');
    }

    public function test_starttime()
    {
        $starttime = time();
        $this->assertGreaterThanOrEqual($starttime, $this->SUT->starttime());
        $this->assertInternalType('integer', $starttime);
        $this->assertFalse($this->SUT->accessed(), 'This method does not flag the session accessed');
    }

    public function test_useragent()
    {
        $this->assertSame('', $this->SUT->agent());
        $this->assertFalse($this->SUT->accessed(), 'This method does not flag the session accessed');
    }

    public function test_isEmpty()
    {
        $this->assertFalse($this->SUT->isEmpty());
        $this->assertFalse($this->SUT->accessed(), 'This method does not flag the session accessed');
    }

    protected function tearDown()
    {
        session_write_close();
    }
}
