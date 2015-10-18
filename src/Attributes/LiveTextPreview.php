<?php namespace Sintattica\Atk\Attributes;
/**
 * This file is part of the ATK distribution on GitHub.
 * Detailed copyright and licensing information can be found
 * in the doc/COPYRIGHT and doc/LICENSE files which should be
 * included in the distribution.
 *
 * @package atk
 * @subpackage attributes
 *
 * @copyright (c)2005 Ivo Jansch
 * @license http://www.achievo.org/atk/licensing ATK Open Source License
 *
 * @version $Revision: 5798 $
 * $Id$
 */
/**
 * Base class include
 */
Tools::useattrib("atkdummyattribute");

/**
 * Custom flags
 */
define("self::AF_LIVETEXT_SHOWLABEL", self::AF_DUMMY_SHOW_LABEL);
define("self::AF_LIVETEXT_NL2BR", self::AF_SPECIFIC_2);

/**
 * The atkLiveTextPreview adds a preview to the page that previews realtime
 * the content of any Attribute or atkTextAttribute while it is being
 * edited.
 *
 * @author Ivo Jansch <ivo@achievo.org>
 * @package atk
 * @subpackage attributes
 *
 */
class LiveTextPreview extends DummyAttribute
{
    var $m_masterattribute = "";

    /**
     * Constructor
     * @param String $name The name of the attribute
     * @param String $masterattribute The attribute that should be previewed.
     * @param int $flags Flags for this attribute. Use self::AF_LIVETEXT_SHOWLABEL if the
     *                   preview should be labeled.
     *                   Use self::AF_LIVETEXT_NL2BR if the data should be nl2br'd before
     *                   display.
     */
    function atkLiveTextPreview($name, $masterattribute, $flags = 0)
    {
        $this->atkDummyAttribute($name, '', $flags);
        $this->m_masterattribute = $masterattribute;
    }

    /**
     * Edit record
     * Thie method will display a live preview.
     * @param array $record Array with fields
     * @param String $fieldprefix Fieldprefix for embedded forms.
     * @return String Parsed string
     */
    function edit($record, $fieldprefix = "")
    {
        $page = Page::getInstance();
        $id = $fieldprefix . $this->fieldName();
        $master = $fieldprefix . $this->m_masterattribute;
        $page->register_scriptcode("function {$id}_ReloadTextDiv()
                                  {
                                    var NewText = document.getElementById('{$master}').value;
                                    var DivElement = document.getElementById('{$id}_preview');
                                    " . ($this->hasFlag(self::AF_LIVETEXT_NL2BR) ? "NewText = NewText.split(/\\n/).join('<br />');"
                : "") . "
                                    DivElement.innerHTML = NewText;
                                  }                                                                    
                                  ");
        $page->register_loadscript("document.entryform.{$this->m_masterattribute}.onkeyup = {$id}_ReloadTextDiv;");

        return '<span id="' . $id . '_preview">' . $record[$this->m_masterattribute] . '</span>';
    }

}

