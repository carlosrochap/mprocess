<?php
/**
 * @package String
 */

/**
 * Bootstrap script
 */
require_once 'init.inc.php';


/**
 * XnBiz decoder test unit
 *
 * @package String
 * @subpackage XnBiz
 */
class String_XnBiz_Test extends PHPUnit_Framework_TestCase
{
    protected $_test_string = array(
        // 'Show'
        '886_920113120411200' =>
            "<script>var rJBK='_xtu}{x';var tLBQCC='-%*--+/';var fOGECG=0;var OOOBM;var wSRV='';var BRLOD=Math.floor(tLBQCC.length/2);while(fOGECG<rJBK.length){OOOBM=rJBK.charCodeAt(fOGECG++);var bQAN=tLBQCC.charCodeAt(OOOBM%tLBQCC.length);bQAN=String.fromCharCode(bQAN);if(bQAN=='L')bQAN='<<';if(bQAN=='~')wSRV+=~OOOBM*(-1);else{wSRV+=Math.floor(eval(OOOBM+bQAN+BRLOD));}}var ab=886;ab+=\"_\";ab+=wSRV; document.getElementById(\"xn_biz\").value=ab;</script>",

        // 'Adorabley'
        '94_268103126348348116' =>
            "<script>var aAHWCV='Ce}WWs';var wRAK='~|L-/';var wOOX=0;var BRLCQ;var tEAMIC='';var nRMPOX=Math.floor(wRAK.length/2);while(wOOX<aAHWCV.length){BRLCQ=aAHWCV.charCodeAt(wOOX++);var iWEL=wRAK.charCodeAt(BRLCQ%wRAK.length);iWEL=String.fromCharCode(iWEL);if(iWEL=='L')iWEL='<<';if(iWEL=='~')tEAMIC+=~BRLCQ*(-1);else{tEAMIC+=Math.floor(eval(BRLCQ+iWEL+nRMPOX));}}var ab=94;ab+=\"_\";ab+=tEAMIC; document.getElementById(\"xn_biz\").value=ab;</script>",
        '522_12142701237770' =>
            "<script>var xJFM='{TDyKD';var tCTPAH='+^~^/';var mYXDCQ=0;var wKPW;var EYSQX='';var SUAXK=Math.floor(tCTPAH.length/2);while(mYXDCQ<xJFM.length){wKPW=xJFM.charCodeAt(mYXDCQ++);var DWOQN=tCTPAH.charCodeAt(wKPW%tCTPAH.length);DWOQN=String.fromCharCode(DWOQN);if(DWOQN=='L')DWOQN='<<';if(DWOQN=='~')EYSQX+=~wKPW*(-1);else{EYSQX+=Math.floor(eval(wKPW+DWOQN+SUAXK));}}var ab=522;ab+=\"_\";ab+=EYSQX; document.getElementById(\"xn_biz\").value=ab;</script>",
    );


    /**
     * Tests decoding
     */
    public function test_decoding()
    {
        foreach ($this->_test_string as $k => $s) {
            $this->assertEquals($k, String_XnBiz::decode($s));
        }
    }
}
