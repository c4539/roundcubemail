<?php

namespace Roundcube\Tests\Framework;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roundcube\Tests\StderrMock;

/**
 * Test class to test rcube_utils class
 */
class UtilsTest extends TestCase
{
    /**
     * Test for rcube_utils::date_format()
     */
    public function test_date_format()
    {
        date_default_timezone_set('Europe/Berlin');

        $this->assertSame(date('d-M-Y H:i:s O'), \rcube_utils::date_format());
        $this->assertSame(date('Y-m-d H:i:s O'), \rcube_utils::date_format('Y-m-d H:i:s O'));

        $result = \rcube_utils::date_format('H:i:s,u O');
        $regexp = '/^' . preg_quote(date('H:i:s,')) . '(?<!000000)\d{6}' . preg_quote(date(' O')) . '$/';

        $this->assertMatchesRegularExpression($regexp, $result);
    }

    /**
     * Test for rcube_utils::explode()
     */
    public function test_explode()
    {
        $this->assertSame(['test', null], \rcube_utils::explode(':', 'test'));
        $this->assertSame(['test1', 'test2'], \rcube_utils::explode(':', 'test1:test2'));
        $this->assertSame(['', 'test1', 'test2'], \rcube_utils::explode(':', ':test1:test2'));
    }

    /**
     * @dataProvider provide_valid_email_cases
     */
    #[DataProvider('provide_valid_email_cases')]
    public function test_valid_email($email, $title)
    {
        $this->assertTrue(\rcube_utils::check_email($email, false), $title);
    }

    /**
     * Valid email addresses for test_valid_email()
     */
    public static function provide_valid_email_cases(): iterable
    {
        return [
            ['email@domain.com', 'Valid email'],
            ['firstname.lastname@domain.com', 'Email contains dot in the address field'],
            ['email@subdomain.domain.com', 'Email contains dot with subdomain'],
            ['firstname+lastname@domain.com', 'Plus sign is considered valid character'],
            ['email@[123.123.123.123]', 'Square bracket around IP address'],
            ['email@[IPv6:::1]', 'Square bracket around IPv6 address (1)'],
            ['email@[IPv6:::1.2.3.4]', 'Square bracket around IPv6 address (2)'],
            ['email@[IPv6:2001:2d12:c4fe:5afe::1]', 'Square bracket around IPv6 address (3)'],
            ['"email"@domain.com', 'Quotes around email is considered valid'],
            ['1234567890@domain.com', 'Digits in address are valid'],
            ['email@domain-one.com', 'Dash in domain name is valid'],
            ['_______@domain.com', 'Underscore in the address field is valid'],
            ['email@domain.name', '.name is valid Top Level Domain name'],
            ['email@domain.co.jp', 'Dot in Top Level Domain name also considered valid (use co.jp as example here)'],
            ['firstname-lastname@domain.com', 'Dash in address field is valid'],
            ['test@xn--e1aaa0cbbbcacac.xn--p1ai', 'IDNA domain'],
            ['あいうえお@domain.com', 'Unicode char as address'],
            ['test@domain.2legit2quit', 'Extended TLD'],
        ];
    }

    /**
     * @dataProvider provide_invalid_email_cases
     */
    #[DataProvider('provide_invalid_email_cases')]
    public function test_invalid_email($email, $title)
    {
        $this->assertFalse(\rcube_utils::check_email($email, false), $title);
    }

    /**
     * Invalid email addresses for test_invalid_email()
     */
    public static function provide_invalid_email_cases(): iterable
    {
        return [
            ['plainaddress', 'Missing @ sign and domain'],
            ['#@%^%#$@#$@#.com', 'Garbage'],
            ['@domain.com', 'Missing username'],
            ['Joe Smith <email@domain.com>', 'Encoded html within email is invalid'],
            ['email.domain.com', 'Missing @'],
            ['email@domain@domain.com', 'Two @ sign'],
            ['.email@domain.com', 'Leading dot in address is not allowed'],
            ['email.@domain.com', 'Trailing dot in address is not allowed'],
            ['email..email@domain.com', 'Multiple dots'],
            ['email@domain.com (Joe Smith)', 'Text followed email is not allowed'],
            ['email@domain', 'Missing top level domain (.com/.net/.org/etc)'],
            ['email@-domain.com', 'Leading dash in front of domain is invalid'],
            // ['email@domain.web', '.web is not a valid top level domain'],
            ['email@123.123.123.123', 'IP address without brackets'],
            ['email@2001:2d12:c4fe:5afe::1', 'IPv6 address without brackets'],
            ['email@IPv6:2001:2d12:c4fe:5afe::1', 'IPv6 address without brackets (2)'],
            ['email@[111.222.333.44444]', 'Invalid IP format'],
            ['email@[111.222.255.257]', 'Invalid IP format (2)'],
            ['email@[.222.255.257]', 'Invalid IP format (3)'],
            ['email@[::1]', 'Invalid IPv6 format (1)'],
            ['email@[IPv6:2001:23x2:1]', 'Invalid IPv6 format (2)'],
            ['email@[IPv6:1111:2222:33333::4444:5555]', 'Invalid IPv6 format (3)'],
            ['email@[IPv6:1111::3333::4444:5555]', 'Invalid IPv6 format (4)'],
            ['email@domain..com', 'Multiple dot in the domain portion is invalid'],
        ];
    }

    /**
     * @dataProvider provide_valid_ip_cases
     */
    #[DataProvider('provide_valid_ip_cases')]
    public function test_valid_ip($ip)
    {
        $this->assertTrue(\rcube_utils::check_ip($ip));
    }

    /**
     * Valid IP addresses for test_valid_ip()
     */
    public static function provide_valid_ip_cases(): iterable
    {
        return [
            ['0.0.0.0'],
            ['123.123.123.123'],
            ['::'],
            ['::1'],
            ['::1.2.3.4'],
            ['2001:2d12:c4fe:5afe::1'],
            ['2001::'],
            ['2001::1'],
        ];
    }

    /**
     * @dataProvider provide_invalid_ip_cases
     */
    #[DataProvider('provide_invalid_ip_cases')]
    public function test_invalid_ip($ip)
    {
        $this->assertFalse(\rcube_utils::check_ip($ip));
    }

    /**
     * Invalid IP addresses for test_invalid_ip()
     */
    public static function provide_invalid_ip_cases(): iterable
    {
        return [
            [''],
            [0],
            ['123.123.123.1234'],
            ['1.1.1.1.1'],
            ['::1.2.3.260'],
            ['::1.0'],
            [':::1'],
            ['2001:::1'],
            ['2001::c4fe:5afe::1'],
            [':c4fe:5afe:1'],
        ];
    }

    /**
     * Test for rep_specialchars_output
     *
     * @dataProvider provide_rep_specialchars_output_cases
     */
    #[DataProvider('provide_rep_specialchars_output_cases')]
    public function test_rep_specialchars_output($type, $mode, $str, $res)
    {
        $result = \rcube_utils::rep_specialchars_output(
            $str, $type ?: 'html', $mode ?: 'strict');

        $this->assertSame($result, $res);
    }

    /**
     * Data for test_rep_specialchars_output()
     */
    public static function provide_rep_specialchars_output_cases(): iterable
    {
        return [
            ['', '', 'abc', 'abc'],
            ['', '', '?', '?'],
            ['', '', '"', '&quot;'],
            ['', '', '<', '&lt;'],
            ['', '', '>', '&gt;'],
            ['', '', '&', '&amp;'],
            ['', '', '&amp;', '&amp;amp;'],
            ['', '', '<a>', '&lt;a&gt;'],
            ['', 'remove', '<a>', ''],
        ];
    }

    /**
     * rcube_utils::mod_css_styles()
     */
    public function test_mod_css_styles()
    {
        $css = file_get_contents(TESTS_DIR . 'src/valid.css');
        $mod = \rcube_utils::mod_css_styles($css, 'rcmbody');

        $this->assertMatchesRegularExpression('/#rcmbody\s+\{/', $mod, 'Replace body style definition');
        $this->assertMatchesRegularExpression('/#rcmbody h1\s\{/', $mod, 'Prefix tag styles (single)');
        $this->assertMatchesRegularExpression('/#rcmbody h1, #rcmbody h2, #rcmbody h3, #rcmbody textarea\s+\{/', $mod, 'Prefix tag styles (multiple)');
        $this->assertMatchesRegularExpression('/#rcmbody \.noscript\s+\{/', $mod, 'Prefix class styles');

        $css = file_get_contents(TESTS_DIR . 'src/media.css');
        $mod = \rcube_utils::mod_css_styles($css, 'rcmbody');

        $this->assertStringContainsString('#rcmbody table[class=w600]', $mod, 'Replace styles nested in @media block');
        $this->assertStringContainsString('#rcmbody { width: 600px', $mod, 'Replace body selector nested in @media block');
        $this->assertStringContainsString('#rcmbody { min-width: 474px', $mod, 'Replace body selector nested in @media block (#5811)');
    }

    /**
     * rcube_utils::mod_css_styles()
     */
    public function test_mod_css_styles_xss()
    {
        $mod = \rcube_utils::mod_css_styles('font-size: 1em;', 'rcmbody');
        $this->assertSame('/* invalid! */', $mod);

        $mod = \rcube_utils::mod_css_styles("@import url('http://localhost/somestuff/css/master.css');", 'rcmbody');
        $this->assertSame('/* evil! */', $mod);

        $mod = \rcube_utils::mod_css_styles("@\\69mport url('http://localhost/somestuff/css/master.css');", 'rcmbody');
        $this->assertSame('/* evil! */', $mod);

        $mod = \rcube_utils::mod_css_styles("@\\5C 69mport url('http://localhost/somestuff/css/master.css'); a { color: red; }", '');
        $this->assertSame('/* evil! */', $mod);

        $mod = \rcube_utils::mod_css_styles("body.main2cols { background-image: url('../images/leftcol.png'); }", 'rcmbody');
        $this->assertSame('#rcmbody.main2cols {}', $mod);

        $mod = \rcube_utils::mod_css_styles('p { left:expression(document.body.offsetWidth-20); }', 'rcmbody');
        $this->assertSame('#rcmbody p {}', $mod);

        $mod = \rcube_utils::mod_css_styles('p { left:exp/*  */ression( alert(&#039;xss3&#039;) ); }', 'rcmbody');
        $this->assertSame('#rcmbody p {}', $mod);

        $mod = \rcube_utils::mod_css_styles("p { background:\\0075\\0072\\00006c('//evil.com/test'); }", 'rcmbody');
        $this->assertSame('#rcmbody p {}', $mod);

        $mod = \rcube_utils::mod_css_styles("p { background: \\75 \\72 \\6C ('/images/img.png'); }", 'rcmbody');
        $this->assertSame('#rcmbody p {}', $mod);

        $mod = \rcube_utils::mod_css_styles("p { background: u\\r\\l('/images/img2.png'); }", 'rcmbody');
        $this->assertSame('#rcmbody p {}', $mod);

        $mod = \rcube_utils::mod_css_styles("p { background: url('//żą.ść?data:image&leak') }", 'rcmbody');
        $this->assertSame('#rcmbody p {}', $mod);

        $mod = \rcube_utils::mod_css_styles("p { background: url('//data:image&leak'); }", 'rcmbody');
        $this->assertSame('#rcmbody p {}', $mod);

        // Note: This looks to me like a bug in browsers, for now we don't allow image-set at all
        $mod = \rcube_utils::mod_css_styles("p { background: image-set('//evil.com/img.png' 1x); }", 'rcmbody');
        $this->assertSame('#rcmbody p {}', $mod);

        $mod = \rcube_utils::mod_css_styles('p { background: none !important; }', 'rcmbody');
        $this->assertSame('#rcmbody p { background: none !important; }', $mod);

        // position: fixed (#5264)
        $mod = \rcube_utils::mod_css_styles('.test { position: fixed; }', 'rcmbody');
        $this->assertSame('#rcmbody .test { position: absolute; }', $mod, 'Replace position:fixed with position:absolute (0)');
        $mod = \rcube_utils::mod_css_styles(".test { position:\nfixed; }", 'rcmbody');
        $this->assertSame('#rcmbody .test { position: absolute; }', $mod, 'Replace position:fixed with position:absolute (1)');
        $mod = \rcube_utils::mod_css_styles('.test { position:/**/fixed; }', 'rcmbody');
        $this->assertSame('#rcmbody .test { position: absolute; }', $mod, 'Replace position:fixed with position:absolute (2)');

        // position: fixed (#6898)
        $mod = \rcube_utils::mod_css_styles('.test { position : fixed; top: 0; }', 'rcmbody');
        $this->assertSame('#rcmbody .test { position: absolute; top: 0; }', $mod, 'Replace position:fixed with position:absolute (3)');
        $mod = \rcube_utils::mod_css_styles('.test { position/**/: fixed; top: 0; }', 'rcmbody');
        $this->assertSame('#rcmbody .test { position: absolute; top: 0; }', $mod, 'Replace position:fixed with position:absolute (4)');
        $mod = \rcube_utils::mod_css_styles(".test { position\n: fixed; top: 0; }", 'rcmbody');
        $this->assertSame('#rcmbody .test { position: absolute; top: 0; }', $mod, 'Replace position:fixed with position:absolute (5)');

        // allow data URIs with images (#5580)
        $mod = \rcube_utils::mod_css_styles('body { background-image: url(data:image/png;base64,123); }', 'rcmbody');
        $this->assertStringContainsString('#rcmbody { background-image: url(data:image/png;base64,123);', $mod, 'Data URIs in url() allowed [1]');
        $mod = \rcube_utils::mod_css_styles('body { background-image: url(data:image/png;base64,123); }', 'rcmbody', true);
        $this->assertStringContainsString('#rcmbody { background-image: url(data:image/png;base64,123);', $mod, 'Data URIs in url() allowed [2]');

        // Allow strict url()
        $mod = \rcube_utils::mod_css_styles('body { background-image: url(http://example.com); }', 'rcmbody', true);
        $this->assertStringContainsString('#rcmbody { background-image: url(http://example.com);', $mod, 'Strict URIs in url() allowed with $allow_remote=true');

        // XSS issue, HTML in 'content' property
        $style = "body { content: '</style><img src onerror=\"alert(\\'hello\\');\">'; color: red; }";
        $mod = \rcube_utils::mod_css_styles($style, 'rcmbody', true);
        $this->assertSame("#rcmbody { content: ''; color: red; }", $mod);

        $style = "body { content: '< page: ;/style>< page: ;img src onerror=\"alert(\\'hello\\');\">'; color: red; }";
        $mod = \rcube_utils::mod_css_styles($style, 'rcmbody', true);
        $this->assertSame("#rcmbody { content: '< page: ;/style>< page: ;img src onerror=\"alert('hello');\">'; color: red; }", $mod);

        // Removing page: property
        $style = 'body { page: test; color: red }';
        $mod = \rcube_utils::mod_css_styles($style, 'rcmbody', true);
        $this->assertSame('#rcmbody { color: red; }', $mod);

        $style = 'body { background:url(alert(&#039;URL!&#039;)); }';
        $mod = \rcube_utils::mod_css_styles($style, 'rcmbody', true);
        $this->assertSame('#rcmbody {}', $mod);
    }

    /**
     * rcube_utils::mod_css_styles()'s prefix argument handling
     */
    public function test_mod_css_styles_prefix()
    {
        $css = '
            .one { font-size: 10pt; }
            .three.four { font-weight: bold; }
            #id1 { color: red; }
            #id2.class:focus { color: white; }
            .five:not(.test), { background: transparent; }
            div .six { position: absolute; }
            p > i { font-size: 120%; }
            div#some { color: yellow; }
            @media screen and (max-width: 699px) and (min-width: 520px) {
                li a.button { padding-left: 30px; }
            }
            :root * { color: red; }
            :root > * { top: 0; }
        ';
        $mod = \rcube_utils::mod_css_styles($css, 'rc', true, 'test');

        $this->assertStringContainsString('#rc .testone', $mod);
        $this->assertStringContainsString('#rc .testthree.testfour', $mod);
        $this->assertStringContainsString('#rc #testid1', $mod);
        $this->assertStringContainsString('#rc #testid2.testclass:focus', $mod);
        $this->assertStringContainsString('#rc .testfive:not(.testtest)', $mod);
        $this->assertStringContainsString('#rc div .testsix', $mod);
        $this->assertStringContainsString('#rc p > i ', $mod);
        $this->assertStringContainsString('#rc div#testsome', $mod);
        $this->assertStringContainsString('#rc li a.testbutton', $mod);
        $this->assertStringNotContainsString(':root', $mod);
        $this->assertStringContainsString('#rc * ', $mod);
        $this->assertStringContainsString('#rc > * ', $mod);
    }

    public function test_xss_entity_decode()
    {
        $mod = \rcube_utils::xss_entity_decode('&lt;img/src=x onerror=alert(1)// </b>');
        $this->assertStringNotContainsString('<img', $mod, 'Strip (encoded) tags from style node');

        $mod = \rcube_utils::xss_entity_decode('#foo:after{content:"\003Cimg/src=x onerror=alert(2)>";}');
        $this->assertStringNotContainsString('<img', $mod, 'Strip (encoded) tags from content property');

        $mod = \rcube_utils::xss_entity_decode("background: u\\r\\00006c('/images/img.png')");
        $this->assertStringContainsString('url(', $mod, 'Escape sequences resolving');

        // #5747
        $mod = \rcube_utils::xss_entity_decode('<!-- #foo { content:css; } -->');
        $this->assertStringContainsString('#foo', $mod, 'Strip HTML comments from content, but not the content');
    }

    /**
     * Test parse_css_block()
     *
     * @dataProvider provide_explode_style_cases
     */
    #[DataProvider('provide_explode_style_cases')]
    public function test_explode_style($input, $output)
    {
        $this->assertSame($output, \rcube_utils::parse_css_block($input));
    }

    /**
     * Test-Cases for parse_css_block() test
     */
    public static function provide_explode_style_cases(): iterable
    {
        return [
            [
                'test:test2',
                [['test', 'test2']],
            ],
            [
                'Test :teSt2 ;',
                [['test', 'teSt2']],
            ],
            [
                'test : test2 test3;',
                [['test', 'test2 test3']],
            ],
            [
                '/* : */ test : val /* ; */ ;',
                [['test', 'val']],
            ],
            [
                '/* test : val */ ;',
                [],
            ],
            [
                'test :"test1\"test2" ;',
                [['test', '"test1\"test2"']],
            ],
            [
                "test : 'test5 \\'test6';",
                [['test', "'test5 \\'test6'"]],
            ],
            [
                "test: test8\ntest6;",
                [['test', 'test8 test6']],
            ],
            [
                "PRop: val1; prop-two: 'val2: '",
                [['prop', 'val1'], ['prop-two', "'val2: '"]],
            ],
            [
                'prop: val1; prop-two :',
                [['prop', 'val1']],
            ],
            [
                'prop: val1; prop-two :;',
                [['prop', 'val1']],
            ],
            [
                'background: url(data:image/png;base64,test)',
                [['background', 'url(data:image/png;base64,test)']],
            ],
            [
                "background:url('data:image/png;base64,test')",
                [['background', "url('data:image/png;base64,test')"]],
            ],
            [
                'background: url("data:image/png;base64,test")',
                [['background', 'url("data:image/png;base64,test")']],
            ],
            [
                'font-family:"新細明體","serif";color:red',
                [['font-family', '"新細明體","serif"'], ['color', 'red']],
            ],
        ];
    }

    /**
     * Check rcube_utils::explode_quoted_string()
     */
    public function test_explode_quoted_string()
    {
        $data = [
            '"a,b"' => ['"a,b"'],
            '"a,b","c,d"' => ['"a,b"', '"c,d"'],
            '"a,\"b",d' => ['"a,\"b"', 'd'],
            'a,' => ['a', ''],
            '"a,' => ['"a,'],
            '"a,\\' => ['"a,\\'],
        ];

        foreach ($data as $text => $res) {
            $result = \rcube_utils::explode_quoted_string(',', $text);
            $this->assertSame($res, $result);
        }
    }

    /**
     * Check rcube_utils::explode_quoted_string() compat. with explode()
     */
    public function test_explode_quoted_string_compat()
    {
        $data = ['', 'a,b,c', 'a', ',', ',a'];

        foreach ($data as $text) {
            $result = \rcube_utils::explode_quoted_string(',', $text);
            $this->assertSame(explode(',', $text), $result);
        }
    }

    /**
     * Check rcube_utils::explode_quoted_string() with multibyte delimiter
     */
    public function test_explode_quoted_string_multibyte_delimiter()
    {
        $data = [
            "a\nb" => ['a', 'b'],
            "a\r\nb" => ['a', 'b'],
            "a\r\n\nb" => ['a', 'b'],
            "\"a\n\\\"\n\"\nb" => ["\"a\n\\\"\n\"", 'b'],
        ];

        foreach ($data as $text => $res) {
            $result = \rcube_utils::explode_quoted_string('[\n\r]+', $text);
            $this->assertSame($res, $result);
        }
    }

    /**
     * rcube_utils::get_boolean()
     */
    public function test_get_boolean()
    {
        $input = [
            false, 'false', '0', 'no', 'off', 'nein', 'FALSE', '', null,
        ];

        foreach ($input as $idx => $value) {
            $this->assertFalse(\rcube_utils::get_boolean($value), "Invalid result for {$idx} test item");
        }

        $input = [
            true, 'true', '1', 1, 'yes', 'anything', 1000,
        ];

        foreach ($input as $idx => $value) {
            $this->assertTrue(\rcube_utils::get_boolean($value), "Invalid result for {$idx} test item");
        }
    }

    /**
     * rcube_utils::get_input_string()
     */
    public function test_get_input_string()
    {
        $_GET = [];
        $this->assertSame('', \rcube_utils::get_input_string('test', \rcube_utils::INPUT_GET));

        $_GET = ['test' => 'val'];
        $this->assertSame('val', \rcube_utils::get_input_string('test', \rcube_utils::INPUT_GET));

        $_GET = ['test' => ['val1', 'val2']];
        $this->assertSame('', \rcube_utils::get_input_string('test', \rcube_utils::INPUT_GET));
    }

    /**
     * rcube_utils::is_simple_string()
     */
    public function test_is_simple_string()
    {
        $this->assertTrue(\rcube_utils::is_simple_string('some-thing.123_'));
        $this->assertFalse(\rcube_utils::is_simple_string(''));
        $this->assertFalse(\rcube_utils::is_simple_string(' '));
        $this->assertFalse(\rcube_utils::is_simple_string('some–thing'));
        $this->assertFalse(\rcube_utils::is_simple_string('some=thing'));
        $this->assertFalse(\rcube_utils::is_simple_string('some thing'));
        $this->assertFalse(\rcube_utils::is_simple_string('some!thing'));
        $this->assertFalse(\rcube_utils::is_simple_string('%20'));
        $this->assertFalse(\rcube_utils::is_simple_string('\0000'));
        $this->assertFalse(\rcube_utils::is_simple_string(1));
        $this->assertFalse(\rcube_utils::is_simple_string(new \stdClass()));
        $this->assertFalse(\rcube_utils::is_simple_string(null));
    }

    /**
     * rcube:utils::file2class()
     */
    public function test_file2class()
    {
        $test = [
            ['', '', 'unknown'],
            ['text', 'text', 'text'],
            ['image/png', 'image.png', 'image png'],
        ];

        foreach ($test as $v) {
            $result = \rcube_utils::file2class($v[0], $v[1]);
            $this->assertSame($v[2], $result);
        }
    }

    /**
     * rcube:utils::strtotime()
     */
    public function test_strtotime()
    {
        // this test depends on system timezone if not set
        date_default_timezone_set('UTC');

        $test = [
            '1' => 1,
            '' => 0,
            'abc-555' => 0,
            '2013-04-22' => 1366588800,
            '2013/04/22' => 1366588800,
            '2013.04.22' => 1366588800,
            '22-04-2013' => 1366588800,
            '22/04/2013' => 1366588800,
            '22.04.2013' => 1366588800,
            '22.4.2013' => 1366588800,
            '20130422' => 1366588800,
            '2013/06/21 12:00:00 UTC' => 1371816000,
            '2013/06/21 12:00:00 Europe/Berlin' => 1371808800,
        ];

        foreach ($test as $datetime => $ts) {
            $result = \rcube_utils::strtotime($datetime);
            $this->assertSame($ts, $result, "Error parsing date: {$datetime}");
        }
    }

    /**
     * rcube:utils::anytodatetime()
     */
    public function test_anytodatetime()
    {
        $test = [
            '2013-04-22' => '2013-04-22',
            '2013/04/22' => '2013-04-22',
            '2013.04.22' => '2013-04-22',
            '22-04-2013' => '2013-04-22',
            '22/04/2013' => '2013-04-22',
            '22.04.2013' => '2013-04-22',
            '04/22/2013' => '2013-04-22',
            '22.4.2013' => '2013-04-22',
            '20130422' => '2013-04-22',
            '1900-10-10' => '1900-10-10',
            '01-01-1900' => '1900-01-01',
            '01/30/1960' => '1960-01-30',
            '1960.12.11 01:02:00' => '1960-12-11',
        ];

        foreach ($test as $datetime => $ts) {
            $result = \rcube_utils::anytodatetime($datetime);
            $this->assertSame($ts, $result ? $result->format('Y-m-d') : false, "Error parsing date: {$datetime}");
        }

        $test = [
            '12/11/2013 01:02:00' => '2013-11-12 01:02:00',
            '1960.12.11 01:02:00' => '1960-12-11 01:02:00',
        ];

        foreach ($test as $datetime => $ts) {
            $result = \rcube_utils::anytodatetime($datetime);
            $this->assertSame($ts, $result ? $result->format('Y-m-d H:i:s') : false, "Error parsing date: {$datetime}");
        }

        $test = [
            'Sun, 4 Mar 2018 03:32:08 +0300 (MSK)' => '2018-03-04 03:32:08 +0300',
        ];

        foreach ($test as $datetime => $ts) {
            $result = \rcube_utils::anytodatetime($datetime);
            $this->assertSame($ts, $result ? $result->format('Y-m-d H:i:s O') : false, "Error parsing date: {$datetime}");
        }
    }

    /**
     * rcube:utils::anytodatetime()
     */
    public function test_anytodatetime_timezone()
    {
        $tz = new \DateTimeZone('Europe/Helsinki');
        $test = [
            'Jan 1st 2014 +0800' => '2013-12-31 18:00', // result in target timezone
            'Jan 1st 14 45:42' => '2014-01-01 00:00', // force fallback to rcube_utils::strtotime()
            'Jan 1st 2014 UK' => '2014-01-01 00:00',
            '1520587800' => '2018-03-09 11:30',  // unix timestamp conversion
            'Invalid date' => false,
        ];

        foreach ($test as $datetime => $ts) {
            $result = \rcube_utils::anytodatetime($datetime, $tz);
            if ($result) {
                // move to target timezone for comparison
                $result->setTimezone($tz);
            }
            $this->assertSame($ts, $result ? $result->format('Y-m-d H:i') : false, "Error parsing date: {$datetime}");
        }
    }

    /**
     * rcube:utils::format_datestr()
     */
    public function test_format_datestr()
    {
        $test = [
            ['abc-555', 'abc', 'abc-555'],
            ['2013-04-22', 'Y-m-d', '2013-04-22'],
            ['22/04/2013', 'd/m/Y', '2013-04-22'],
            ['4.22.2013', 'm.d.Y', '2013-04-22'],
        ];

        foreach ($test as $data) {
            $result = \rcube_utils::format_datestr($data[0], $data[1]);
            $this->assertSame($data[2], $result, 'Error formatting date: ' . $data[0]);
        }
    }

    /**
     * rcube:utils::tokenize_string()
     */
    public function test_tokenize_string()
    {
        $test = [
            '' => [],
            'abc d' => ['abc'],
            'abc de' => ['abc', 'de'],
            'äàé;êöü-xyz' => ['äàé', 'êöü', 'xyz'],
            '日期格式' => ['日期格式'],
        ];

        foreach ($test as $input => $output) {
            $result = \rcube_utils::tokenize_string($input);
            $this->assertSame($output, $result);
        }
    }

    /**
     * rcube:utils::normalize_string()
     */
    public function test_normalize_string()
    {
        $test = [
            '' => '',
            'abc def' => 'abc def',
            'ÇçäâàåæéêëèïîìÅÉöôòüûùÿøØáíóúñÑÁÂÀãÃÊËÈÍÎÏÓÔõÕÚÛÙýÝ' => 'ccaaaaaeeeeiiiaeooouuuyooaiounnaaaaaeeeiiioooouuuyy',
            'ąáâäćçčéęëěíîłľĺńňóôöŕřśšşťţůúűüźžżýĄŚŻŹĆ' => 'aaaaccceeeeiilllnnooorrsssttuuuuzzzyaszzc',
            'ßs' => 'sss',
            'Xae' => 'xa',
            'Xoe' => 'xo',
            'Xue' => 'xu',
            '项目' => '项目',
            'ß' => '',
            '日' => '',
        ];

        foreach ($test as $input => $output) {
            $result = \rcube_utils::normalize_string($input);
            $this->assertSame($output, $result, "Error normalizing '{$input}'");
        }
    }

    /**
     * rcube_utils::words_match()
     */
    public function test_words_match()
    {
        $bin = base64_decode('R0lGODlhDwAPAIAAAMDAwAAAACH5BAEAAAAALAAAAAAPAA8AQAINhI+py+0Po5y02otnAQA7', false);
        $test = [
            ['', 'test', false],
            ['test', 'test', true],
            ['test', 'none', false],
            ['test', 'test xyz', false],
            ['test xyz', 'test xyz', true],
            ['this is test', 'test', true],
            // try some binary content
            ['this is test ' . $bin, 'test', true],
            ['this is test ' . $bin, 'none', false],
        ];

        foreach ($test as $idx => $params) {
            $result = \rcube_utils::words_match($params[0], $params[1]);
            $this->assertSame($params[2], $result, "words_match() at index {$idx}");
        }
    }

    /**
     * rcube:utils::is_absolute_path()
     */
    public function test_is_absolute_path()
    {
        if (strtoupper(substr(\PHP_OS, 0, 3)) == 'WIN') {
            $test = [
                '' => false,
                'C:\\' => true,
                'some/path' => false,
            ];
        } else {
            $test = [
                '' => false,
                '/path' => true,
                'some/path' => false,
            ];
        }

        foreach ($test as $input => $output) {
            $result = \rcube_utils::is_absolute_path($input);
            $this->assertSame($output, $result);
        }
    }

    /**
     * rcube:utils::random_bytes()
     */
    public function test_random_bytes()
    {
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{15}$/', \rcube_utils::random_bytes(15));
        $this->assertSame(15, strlen(\rcube_utils::random_bytes(15, true)));
        $this->assertSame(1, strlen(\rcube_utils::random_bytes(1)));
        $this->assertSame(0, strlen(\rcube_utils::random_bytes(0)));
        $this->assertSame(0, strlen(\rcube_utils::random_bytes(-1)));
    }

    /**
     * Test idn_to_ascii
     *
     * @param string $decoded Decoded email address
     * @param string $encoded Encoded email address
     *
     * @dataProvider provide_idn_convert_cases
     */
    #[DataProvider('provide_idn_convert_cases')]
    public function test_idn_to_ascii($decoded, $encoded)
    {
        $this->assertSame(\rcube_utils::idn_to_ascii($decoded), $encoded);
    }

    /**
     * Test idn_to_utf8
     *
     * @param string $decoded Decoded email address
     * @param string $encoded Encoded email address
     *
     * @dataProvider provide_idn_convert_cases
     */
    #[DataProvider('provide_idn_convert_cases')]
    public function test_idn_to_utf8($decoded, $encoded)
    {
        $this->assertSame(\rcube_utils::idn_to_utf8($encoded), $decoded);
    }

    /**
     * Test-Cases for IDN to ASCII and IDN to UTF-8
     */
    public static function provide_idn_convert_cases(): iterable
    {
        /*
         * Check https://en.wikipedia.org/wiki/List_of_Internet_top-level_domains#Internationalized_brand_top-level_domains
         * and https://github.com/true/php-punycode/blob/master/tests/PunycodeTest.php for more Test-Data
         */

        return [
            ['test@vermögensberater', 'test@xn--vermgensberater-ctb'],
            ['test@vermögensberatung', 'test@xn--vermgensberatung-pwb'],
            ['test@グーグル', 'test@xn--qcka1pmc'],
            ['test@谷歌', 'test@xn--flw351e'],
            ['test@中信', 'test@xn--fiq64b'],
            ['test@рф.ru', 'test@xn--p1ai.ru'],
            ['test@δοκιμή.gr', 'test@xn--jxalpdlp.gr'],
            ['test@gwóźdź.pl', 'test@xn--gwd-hna98db.pl'],
            ['рф.ru@рф.ru', 'рф.ru@xn--p1ai.ru'],
            ['vermögensberater', 'xn--vermgensberater-ctb'],
            ['vermögensberatung', 'xn--vermgensberatung-pwb'],
            ['グーグル', 'xn--qcka1pmc'],
            ['谷歌', 'xn--flw351e'],
            ['中信', 'xn--fiq64b'],
            ['рф.ru', 'xn--p1ai.ru'],
            ['δοκιμή.gr', 'xn--jxalpdlp.gr'],
            ['gwóźdź.pl', 'xn--gwd-hna98db.pl'],
            ['fußball.de', 'xn--fuball-cta.de'],
        ];
    }

    /**
     * Test idn_to_ascii with non-domain input (#6224)
     */
    public function test_idn_to_ascii_special()
    {
        $this->assertSame(\rcube_utils::idn_to_ascii('H.S'), 'H.S');
        $this->assertSame(\rcube_utils::idn_to_ascii('d.-h.lastname'), 'd.-h.lastname');
    }

    /**
     * Test parse_host()
     *
     * @dataProvider provide_parse_host_cases
     */
    #[DataProvider('provide_parse_host_cases')]
    public function test_parse_host($name, $host, $result)
    {
        $this->assertSame(\rcube_utils::parse_host($name, $host), $result);
    }

    /**
     * Test-Cases for test_parse_host()
     */
    public static function provide_parse_host_cases(): iterable
    {
        return [
            ['%z', 'hostname', 'hostname'],
            ['%z', 'domain.tld', 'domain.tld'],
            ['%z', 'host.domain.tld', 'domain.tld'],
            ['%z', 'host1.host2.domain.tld', 'host2.domain.tld'],
        ];
    }

    /**
     * Test parse_host_uri()
     *
     * @dataProvider provide_parse_host_uri_cases
     */
    #[DataProvider('provide_parse_host_uri_cases')]
    public function test_parse_host_uri($args, $result)
    {
        $this->assertSame($result, call_user_func_array('rcube_utils::parse_host_uri', $args));
    }

    /**
     * Test-Cases for test_parse_host_uri()
     */
    public static function provide_parse_host_uri_cases(): iterable
    {
        return [
            [['hostname', null, null], ['hostname', null, null]],
            [['hostname:143', null, null], ['hostname', null, 143]],
            [['hostname:143', 123, 345], ['hostname', null, 143]],
            [['tls://host.domain.tld', 143, 993], ['host.domain.tld', 'tls', 143]],
            [['ssl://host.domain.tld', 143, 993], ['host.domain.tld', 'ssl', 993]],
            [['imaps://host.domain.tld', 143, 993], ['host.domain.tld', 'imaps', 993]],
            [['tls://host.domain.tld:123', 143, 993], ['host.domain.tld', 'tls', 123]],
            [['ssl://host.domain.tld:123', 143, 993], ['host.domain.tld', 'ssl', 123]],
            [['imaps://host.domain.tld:123', 143, 993], ['host.domain.tld', 'imaps', 123]],
            [['unix:///var/run/dovecot/imap', null, null], ['unix:///var/run/dovecot/imap', 'unix', -1]],
            [['ldapi:///var/run/ldap.sock', 123, 123], ['ldapi:///var/run/ldap.sock', 'ldapi', -1]],
            [['ldapi://', 123, 123], ['ldapi://', 'ldapi', -1]],
        ];
    }

    /**
     * Test remove_subject_prefix
     *
     * @dataProvider provide_remove_subject_prefix_cases
     */
    #[DataProvider('provide_remove_subject_prefix_cases')]
    public function test_remove_subject_prefix($mode, $subject, $result)
    {
        $this->assertSame(\rcube_utils::remove_subject_prefix($subject, $mode), $result);
    }

    /**
     * Test-Cases for test_remove_subject_prefix()
     */
    public static function provide_remove_subject_prefix_cases(): iterable
    {
        return [
            ['both', 'Fwd: Re: Test subject both', 'Test subject both'],
            ['both', 'Re: Fwd: Test subject both', 'Test subject both'],
            ['reply', 'Fwd: Re: Test subject reply', 'Fwd: Re: Test subject reply'],
            ['reply', 'Re: Fwd: Test subject reply', 'Fwd: Test subject reply'],
            ['reply', 'Re: Fwd: Test subject reply (was: other test)', 'Fwd: Test subject reply'],
            ['forward', 'Re: Fwd: Test subject forward', 'Re: Fwd: Test subject forward'],
            ['forward', 'Fwd: Re: Test subject forward', 'Re: Test subject forward'],
            ['forward', 'Fw: Re: Test subject forward', 'Re: Test subject forward'],
        ];
    }

    /**
     * Test server_name()
     */
    public function test_server_name()
    {
        $this->assertSame('localhost', \rcube_utils::server_name('test'));

        $_SERVER['test'] = 'test.com:843';
        $this->assertSame('test.com', \rcube_utils::server_name('test'));

        $_SERVER['test'] = 'test.com';
        $this->assertSame('test.com', \rcube_utils::server_name('test'));
    }

    /**
     * Test server_name() with trusted_host_patterns
     */
    public function test_server_name_trusted_host_patterns()
    {
        $_SERVER['test'] = 'test.com';

        $rcube = \rcube::get_instance();
        $rcube->config->set('trusted_host_patterns', ['my.domain.tld']);

        StderrMock::start();
        $this->assertSame('localhost', \rcube_utils::server_name('test'));
        StderrMock::stop();
        $this->assertSame("ERROR: Specified host is not trusted. Using 'localhost'.", trim(StderrMock::$output));

        $rcube->config->set('trusted_host_patterns', ['test.com']);

        StderrMock::start();
        $this->assertSame('test.com', \rcube_utils::server_name('test'));
        StderrMock::stop();

        $_SERVER['test'] = 'subdomain.test.com';

        StderrMock::start();
        $this->assertSame('localhost', \rcube_utils::server_name('test'));
        StderrMock::stop();

        $rcube->config->set('trusted_host_patterns', ['^test.com$']);
        $_SERVER['test'] = '^test.com$';

        StderrMock::start();
        $this->assertSame('localhost', \rcube_utils::server_name('test'));
        StderrMock::stop();
    }
}
