<?php

namespace dokuwiki\plugin\menuext;

use dokuwiki\Input\Input;
use dokuwiki\Utf8\PhpString;

/**
 * Custom Menu Item
 */
class MenuExtItem extends \dokuwiki\Menu\Item\AbstractItem
{

    protected $link;
    protected $attributes;

    /**
     * Intitialize a Menu Item with the given data
     *
     * @inheritDoc
     * @param array $data
     */
    public function __construct($data)
    {
        global $conf;

        if (isset($data['label'][$conf['lang']])) $this->label = $data['label'][$conf['lang']];
        if (isset($data['title'][$conf['lang']])) $this->title = $data['title'][$conf['lang']];
        if (isset($data['link'])) $this->link = $data['link'];
        if (isset($data['svg'])) $this->svg = $data['svg'];
        if (isset($data['attr'])) $this->attributes = $data['attr'];

        parent::__construct();
    }

    /** @inheritDoc */
    public function getLink()
    {
        return $this->linkReplacements($this->link);
    }

    /**
     * Auto-Download material design icons
     * @inheritDoc
     */
    public function getSvg()
    {
        if (file_exists($this->svg)) return $this->svg;
        $file = mediaFN($this->svg);
        if (file_exists($file)) return $file;

        $base = 'https://raw.githubusercontent.com/Templarian/MaterialDesign/master/svg/';
        $file = getCacheName($this->svg, '.svg');
        if (!file_exists($file)) {
            io_download($base . $this->svg, $file);
            clearstatcache(true, $file);
        }
        if (file_exists($file)) return $file;

        return DOKU_INC . 'lib/images/menu/00-default_checkbox-blank-circle-outline.svg';
    }

    /** @@inheritDoc */
    public function getLinkAttributes($classprefix = 'menuitem ')
    {
        $attr = parent::getLinkAttributes($classprefix);
        if(is_array($this->attributes)) {
            $attr = array_merge($attr, $this->attributes);
        }
        return $attr;
    }

    /**
     * Replace all placeholders in given link
     *
     * Basically the same as parsePageTemplate but does URL encoding
     *
     * @param string $link the link with placeholders
     * @return string
     * @see parsePageTemplate
     */
    protected function linkReplacements($link)
    {
        global $ID;
        global $USERINFO;
        global $conf;
        /* @var Input $INPUT */
        global $INPUT;

        // replace placeholders
        $file = noNS($ID);
        $page = strtr($file, $conf['sepchar'], ' ');
        $link = strftime($link); // time first
        $link = str_replace(
            [
                '@ID@',
                '@NS@',
                '@CURNS@',
                '@!CURNS@',
                '@!!CURNS@',
                '@!CURNS!@',
                '@FILE@',
                '@!FILE@',
                '@!FILE!@',
                '@PAGE@',
                '@!PAGE@',
                '@!!PAGE@',
                '@!PAGE!@',
                '@USER@',
                '@NAME@',
                '@MAIL@',
                '@DATE@',
            ],
            array_map('rawurlencode', [
                $ID,
                getNS($ID),
                curNS($ID),
                PhpString::ucfirst(curNS($ID)),
                PhpString::ucwords(curNS($ID)),
                PhpString::strtoupper(curNS($ID)),
                $file,
                PhpString::ucfirst($file),
                PhpString::strtoupper($file),
                $page,
                PhpString::ucfirst($page),
                PhpString::ucwords($page),
                PhpString::strtoupper($page),
                $INPUT->server->str('REMOTE_USER'),
                $USERINFO ? $USERINFO['name'] : '',
                $USERINFO ? $USERINFO['mail'] : '',
                $conf['dformat'],
            ]), $link
        );

        return $link;
    }

}
