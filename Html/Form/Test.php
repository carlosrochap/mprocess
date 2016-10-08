<?php
/**
 * @package Html
 */

/**
 * Bootstrap script
 */
require_once 'init.inc.php';


/**
 * HTML forms extractor tests
 *
 * @package Html
 * @subpackage Form
 */
class Html_Form_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Connection to use by test cases
     *
     * @var Connection_Dummy
     */
    private $_connection = null;

    /**
     * Test form object
     *
     * @var Html_Form
     */
    private $_form = null;


    /**
     * Sets up dummy connection and test form object
     */
    public function setUp()
    {
        $this->_connection = new Connection_Dummy();
        $this->_connection->get('http://www.example.com');

        $this->_form = Html_Form::get(
            file_get_contents(str_replace('.php', '.html', __FILE__)),
            $this->_connection,
            array('id' => 'createaccount')
        );
    }

    /**
     * Tests form fields & attributes accessors
     */
    public function test_parser_and_getters()
    {
        $this->assertEquals('1829701015126408007', $this->_form['dsh']);
        $this->assertEquals('1829701015126408007', $this->_form->dsh);
        $this->assertEquals('', $this->_form['ktl']);
        $this->assertEquals('', $this->_form->ktl);
        $this->assertEquals('yes', $this->_form['PersistentCookie']);
        $this->assertEquals('yes', $this->_form->PersistentCookie);
        $this->assertEquals('I accept. Create my account.', $this->_form['submitbutton']);
        $this->assertEquals('I accept. Create my account.', $this->_form->submitbutton);
    }

    /**
     * Tests uploaded files fields handling
     */
    public function test_file_fields_handling()
    {
        $form = new Html_Form(
            '<form action="/"><input type="file" name="upload"/></form>',
            'http://google.com'
        );
        $this->assertEquals(Html_Form::CONTENT_TYPE_URLENCODED, $form->content_type);

        $fn = tempnam(null, mt_rand());
        $form['upload'] = $fn;
        unlink($fn);
        $this->assertEquals(Html_Form::CONTENT_TYPE_MULTIPART, $form->content_type);
        $this->assertEquals("@{$fn}", $form['upload']);
    }

    /**
     * Tests construction of an absolute URL from a relative one using
     * a reference absolute URL
     */
    public function test_absolute_url_constructor()
    {
        $url = 'a/b/c.html';

        $ref_url_host = 'http://www.example.com';
        $ref_url_path = '';

        $this->assertEquals(
            "{$ref_url_host}/{$url}",
            Html_Form::get_absolute_url($url, "{$ref_url_host}{$ref_url_path}")
        );

        $ref_url_path = '/';
        $this->assertEquals(
            "{$ref_url_host}{$ref_url_path}{$url}",
            Html_Form::get_absolute_url($url, "{$ref_url_host}{$ref_url_path}")
        );

        $ref_url_path = '/d/e';
        $this->assertEquals(
            "{$ref_url_host}/d/{$url}",
            Html_Form::get_absolute_url($url, "{$ref_url_host}{$ref_url_path}")
        );

        $url = 'http://example.org/a/b/c.html';
        $this->assertEquals(
            $url,
            Html_Form::get_absolute_url($url, "{$ref_url_host}{$ref_url_path}")
        );

        $url = '?a=b';
        $ref_url = "{$ref_url_host}/a/b/c.html";
        $this->assertEquals(
            "{$ref_url}{$url}",
            Html_Form::get_absolute_url($url, $ref_url)
        );

        $this->assertTrue(0 === strpos($this->_form->action, 'https://www.google.com'));
    }

    /**
     * Tests submitting form data
     */
    public function test_submitter()
    {
        $this->_form->submit($this->_connection);

        $this->assertEquals(
            'post',
            $this->_connection->method
        );
        $this->assertEquals(
            $this->_form->action,
            $this->_connection->last_url
        );

        $args = $this->_connection->args;
        $this->assertEquals(
            (string)$this->_form,
            $args[1]
        );
    }
}
