<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class action_plugin_oiddelegate extends DokuWiki_Action_Plugin {

    /**
     * return some info
     */
    function getInfo() {
        return confToHash(dirname(__FILE__).'/info.txt');
    }

    function register(&$controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT','BEFORE', $this, 'handle_metaheader');
    }

    function handle_metaheader(&$event, $param) {
        global $ACT;
        global $ID;
        if($ACT != 'show') return true;

        // read delegate setups from config
        $delegates = confToHash(DOKU_CONF.'openid-delegates.conf');
        $delegate = $delegates[$ID];   // check delegate for current page
        if(!$delegate) $delegate = $delegates['*']; // fall back to default if any
        if(!$delegate) return true;

        list($oid,$server) = preg_split('/\s+/',$delegate,2);
        $oid = trim($oid);
        $server = trim($server);
        if(!$server) $server = $oid;

        $event->data['link'][] = array(
            'rel'  => 'openid.server',
            'href' => $server,
        );
        $event->data['link'][] = array(
            'rel'  => 'openid.delegate',
            'href' => $oid,
        );
        $event->data['link'][] = array(
            'rel'  => 'openid2.provider',
            'href' => $server,
        );
        $event->data['link'][] = array(
            'rel'  => 'openid2.localid',
            'href' => $oid,
        );

        return true;
    }
}
