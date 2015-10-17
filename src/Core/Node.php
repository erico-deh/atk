<?php namespace Sintattica\Atk\Core;

use Sintattica\Atk\Attributes\Attribute;
use Sintattica\Atk\Attributes\FieldSet;
use Sintattica\Atk\Handlers\ActionHandler;
use Sintattica\Atk\Session\SessionManager;
use Sintattica\Atk\Ui\Page;
use Sintattica\Atk\Ui\Ui;
use Sintattica\Atk\Lock\Lock;
use Sintattica\Atk\Ui\PageBuilder;
use Sintattica\Atk\Ui\Output;
use Sintattica\Atk\Ui\Theme;
use Sintattica\Atk\Db\Db;
use Sintattica\Atk\Utils\Selector;
use Sintattica\Atk\Security\SecurityManager;
use Sintattica\Atk\Session\State;
use Sintattica\Atk\Utils\ActionListener;
use Sintattica\Atk\RecordList\ColumnConfig;
use Sintattica\Atk\Relations\ManyToOneRelation;
use Sintattica\Atk\Utils\StringParser;
use Sintattica\Atk\Utils\Debugger;
use \Exception;

/**
 * Define some flags for nodes. Use the constructor of the Node
 * class to set the flags. (concatenate multiple flags with '|')
 */
/**
 * No new records may be added
 */
define("NF_NO_ADD", 1);

/**
 * Records may not be edited
 */
define("NF_NO_EDIT", 2);

/**
 * Records may not be deleted
 */
define("NF_NO_DELETE", 4);

/**
 * Immediately after you add a new record,
 * you get the editpage for that record
 */
define("NF_EDITAFTERADD", 8);

/**
 * Records may not be searched
 */
define("NF_NO_SEARCH", 16);

/**
 * Ignore addFilter filters
 */
define("NF_NO_FILTER", 32);

/**
 * Doesn't show an add form on the admin page
 * but a link to the form
 */
define("NF_ADD_LINK", 64);

/**
 * Records may not be viewed
 */
define("NF_NO_VIEW", 128);

/**
 * Records / trees may be copied
 */
define("NF_COPY", 256);

/**
 * If this flag is set and only one record is
 * present on a selectpage, atk automagically
 * selects it and moves on to the target
 */
define("NF_AUTOSELECT", 512);

/**
 * If set, atk stores the old values of
 * a record as ["atkorgrec"] in the $rec that
 * gets passed to the postUpdate
 */
define("NF_TRACK_CHANGES", 1024);

/**
 * Quick way to disable accessright checking
 * for an entire node. (Everybody may access this node)
 */
define("NF_NO_SECURITY", 2048);

/**
 * Extended search feature is turned off
 */
define("NF_NO_EXTENDED_SEARCH", 4096);

/**
 * Multi-selection of records is turned on
 */
define("NF_MULTI_RECORD_ACTIONS", 8192);

/**
 * Multi-priority-selection of records is turned on
 */
define("NF_MRPA", 16384);

/**
 * Add locking support to node, if one user is editing a record,
 * no one else may edit it.
 */
define("NF_LOCK", 32768);

/**
 * Quick way to ensable the csv import feature
 */
define("NF_IMPORT", 131072);

/**
 * Add CSV export ability to the node.
 */
define("NF_EXPORT", 262144);


/**
 * Disable csv import feature
 * @deprecated since ATK 5.2
 */
define("NF_NO_IMPORT", 0);

/**
 * Enable extended sorting (multicolumn sort)
 */
define("NF_EXT_SORT", 524288);

/**
 * Makes a node cache it's recordlist
 */
define("NF_CACHE_RECORDLIST", 1048576);

/**
 * After adding a new record add another one instantaniously.
 */
define("NF_ADDAFTERADD", 2097152);

/**
 * No sorting possible.
 */
define("NF_NO_SORT", 4194304);

/**
 * Use the dialog popup box when adding a new record for this node.
 */
define("NF_ADD_DIALOG", 8388608);

/**
 * Use the dialog add-or-copy popup box when adding a new record for this node.
 */
define("NF_ADDORCOPY_DIALOG", 16777216);

/**
 * Specific node flag 1
 */
define("NF_SPECIFIC_1", 33554432);

/**
 * Specific node flag 2
 */
define("NF_SPECIFIC_2", 67108864);

/**
 * Specific node flag 3
 */
define("NF_SPECIFIC_3", 134217728);

/**
 * Specific node flag 4
 */
define("NF_SPECIFIC_4", 268435456);

/**
 * Specific node flag 5
 */
define("NF_SPECIFIC_5", 536870912);

/**
 * Records may be copied and open for editing
 */
define("NF_EDITAFTERCOPY", 1073741824);

/**
 * Alias for NF_MULTI_RECORD_ACTIONS flag (shortcut)
 */
define("NF_MRA", NF_MULTI_RECORD_ACTIONS);


/**
 * Aggregate flag to quickly create readonly nodes
 */
define("NF_READONLY", NF_NO_ADD | NF_NO_DELETE | NF_NO_EDIT);

/**
 * action status flags
 * Note that these have binary numbers, even though an action could never have
 * two statusses at the same time.
 * This is done however, so the flags can be used as a mask in the setFeedback
 * function.
 */
/**
 * The action is cancelled
 *
 * action status flag
 */
define("ACTION_CANCELLED", 1);

/**
 * The action failed to accomplish it's goal
 *
 * action status flag
 */
define("ACTION_FAILED", 2);

/**
 * The action is a success
 *
 * action status flag
 */
define("ACTION_SUCCESS", 4);

/**
 * Trigger flags
 */
define("TRIGGER_NONE", 0);
define("TRIGGER_AUTO", 1);
define("TRIGGER_PRE", 2);
define("TRIGGER_POST", 4);
define("TRIGGER_ALL", TRIGGER_PRE | TRIGGER_POST);

/**
 * Multi-record-actions selection modes. These
 * modes are mutually exclusive.
 */
/**
 * Multiple selections possible.
 */
define("MRA_MULTI_SELECT", 1);

/**
 * Only one selection possible.
 */
define("MRA_SINGLE_SELECT", 2);

/**
 * No selection possible (e.g. action is always for all (visible) records!).
 */
define("MRA_NO_SELECT", 3);

/**
 * The Node class represents a piece of information that is part of an
 * application. This class provides standard functionality for adding,
 * editing and deleting nodes.
 * This class must be seen as an abstract base class: For every piece of
 * information in an application, a class must be derived from this class
 * with specific implementations for that type of node.
 *
 * @author Ivo Jansch <ivo@achievo.org>
 * @package atk
 */
class Node
{
    /**
     * reference to the class which is used to validate atknodes
     * the validator is overridable by changing this variabele
     *
     * @access private
     * @var String
     */
    var $m_validate_class = "atk.atknodevalidator";

    /**
     * Unique field sets of a certain node.
     *
     * Indicates which field combinations should be unique.
     * It doesn't contain the unique fields which have been set by flag
     * AF_UNIQUE.
     *
     * @access private
     * @var array
     */
    var $m_uniqueFieldSets = array();

    /**
     * Nodes must be initialised using the init() function before they can be
     * used. This member indicated whether the node has been initialised.
     * @access private
     * @var boolean
     */
    var $m_initialised = false;

    /**
     * Check to prevent double execution of setAttribSizes on pages with more
     * than one form.
     * @access private
     * @var boolean
     */
    var $m_attribsizesset = false;

    /**
     * The list of attributes of a node. These should be of the class
     * Attribute or one of its derivatives.
     * @access private
     * @var Attribute[]
     */
    var $m_attribList = array();

    /**
     * Index list containing the attributes in the order in which they will
     * appear on screen.
     * @access private
     * @var array
     */
    var $m_attribIndexList = array();

    /**
     * Reference to the page on which the node is rendering its output.
     * @access private
     * @var Page
     */
    var $m_page = null;

    /**
     * List of available tabs. Associative array structured like this:
     * array($action=>$arrayOfTabnames)
     * @access private
     * @var array
     */
    var $m_tabList = array();

    /**
     * List of available sections. Associative array structured like this:
     * array($action=>$arrayOfSectionnames)
     * @access private
     * @var array
     */
    var $m_sectionList = array();

    /**
     * Keep track of tabs per attribute.
     * @access private
     * @var array
     */
    var $m_attributeTabs = array();

    /**
     * Keep track if a tab contains attribs (checkEmptyTabs function)
     * @access private
     * @var array
     */
    var $m_filledTabs = array();

    /**
     * The nodetype.
     * @access protected
     * @var String
     */
    var $m_type;

    /**
     * The module of the node.
     * @access protected
     * @var String
     */
    var $m_module;

    /**
     * The database that the node is using for storing and loading its data.
     * @access protected
     * @var mixed
     */
    var $m_db = null;

    /**
     * The table to use for data storage.
     * @access protected
     * @var String
     */
    var $m_table;

    /**
     * The name of the sequence used for autoincrement fields.
     * @access protected
     * @var String
     */
    var $m_seq;

    /**
     * List of names of the attributes that form this node's primary key.
     * @access protected
     * @var array
     */
    var $m_primaryKey = array();

    /**
     * The postvars (or getvars) that are passed to a page will be passed
     * to the class using the dispatch function. We store them in a member
     * variable for easy access.
     * @access protected
     * @var array
     */
    var $m_postvars = array();

    /**
     * The action that the node is currently performing.
     * @access protected
     * @var String
     */
    var $m_action;

    /**
     * Contains the definition of what needs to rendered partially.
     * If set to NULL not in partial rendering mode.
     */
    var $m_partial = null;

    /**
     * The active action handler.
     * @access protected
     * @var ActionHandler
     */
    var $m_handler = null;

    /**
     * Default order by statement.
     * @access protected
     * @var String
     */
    var $m_default_order = "";

    /**
     * Bitwise mask of node flags (NF_* flags).
     * @var int
     */
    var $m_flags;

    /*
     * Name of the field that is used for creating an alphabetical index in
     * admin/select pages.
     * @access private
     * @var String
     */
    var $m_index = "";

    /**
     * Default tab being displayed in add/edit mode.
     * @access private
     * @var String
     */
    var $m_default_tab = "default";

    /**
     * Default sections that are expanded.
     * @access private
     * @var String
     */
    var $m_default_expanded_sections = array();

    /**
     * Record filters, in attributename/required value pairs.
     * @access private
     * @var array
     */
    var $m_filters = array();

    /**
     * Record filters, as a list of sql statements.
     * @access private
     * @var array
     */
    var $m_fuzzyFilters = array();

    /**
     * For speed, we keep track of a list of attributes that we don't have to
     * load in recordlists.
     * @access protected
     * @var array
     */
    var $m_listExcludes = array();

    /**
     * For speed, we keep track of a list of attributes that we don't have to
     * load when in view pages.
     * @todo This can probably be moved to the view handler.
     * @access protected
     * @var array
     */
    var $m_viewExcludes = array();

    /**
     * For speed, we keep track of a list of attributes that have the cascade
     * delete flag set.
     * @todo This should be moved to the delete handler, or should not be
     *       cached at all. (caching this on each load is slower than just
     *       retrieving the list when it's needed)
     * @access private
     * @var array
     */
    var $m_cascadingAttribs = array();

    /**
     * Actions are mapped to security units.
     *
     * For example, both actions "save" and "add" require access "add". If an
     * item is not in this list, it's treated 'as-is'. Derived nodes may add
     * more mappings to tell the systems that some custom actions require the
     * same privilege as others.
     * Structure: array($action=>$requiredPrivilege)
     * @access protected
     * @var array
     */
    var $m_securityMap = array(
        "save" => "add",
        "update" => "edit",
        "multiupdate" => "edit",
        "copy" => "add",
        "import" => "add",
        "editcopy" => "add",
        "search" => "admin",
        "smartsearch" => "admin"
    );

    /**
     * The right to execute certain actions can be implied by the fact that you
     * have some other right. For example, if you have the right to access a
     * feature (admin right), you may also view that record, and don't need
     * explicit rights to view it. So the 'view' right is said to be 'implied'
     * by the 'admin' right.
     * This is a subtle difference with m_securityMap.
     * @access protected
     * @var array
     */
    var $m_securityImplied = array("view" => "admin");

    /**
     * Name of the node that is used for privilege checking.
     *
     * If a class is named 'project', then by default, if the system needs to
     *  know whether a user may edit a record, the securitymanager searches
     * for 'edit' access on 'project'. However, if an alias is set here, the
     * securitymanger searches for 'edit' on that alias.
     * @access private
     * @var String
     */
    var $m_securityAlias = "";

    /*
     * Nodes can specify actions that require no access level
     * Note: for the moment, the "select" action is always allowed.
     * @todo This may not be correct. We have to find a way to bind the
     * select action to the action that follows after the select.
     * @access private
     * @var array
     */
    var $m_unsecuredActions = array("select", "multiselect", "feedback");

    /*
     *
     * Boolean that is set to true when the stacktrace is displayed, so it
     * is displayed only once.
     * @deprecated This member is as deprecated as the statusbar() method.
     * @access private
     * @var boolean
     */
    var $m_statusbarDone = false;

    /**
     * Auto search-actions; action that will be performed if only one record
     * is found.
     * @access private
     * @var array
     */
    var $m_search_action;

    /**
     * Priority actions
     * @access private
     * @todo This, and the priority_min/max members, should be moved
     *       to the recordlist
     * @var array
     */
    var $m_priority_actions = array();

    /**
     * Minimum for the mra priority select
     * @access private
     * @var int
     */
    var $m_priority_min = 1;

    /**
     * Maximum for the mra priority select
     * @access private
     * @var int
     */
    var $m_priority_max = 0;

    /**
     * The lock instance
     * @access protected
     * @var Lock
     */
    var $m_lock = null;

    /**
     * List of actions that should give success/failure feedback
     * @access private
     * @var array
     */
    var $m_feedback = array();

    /**
     * Number to use with numbering
     * @access protected
     * @var mixed
     */
    var $m_numbering = null;

    /**
     * Descriptor template.
     * @access protected
     * @var String
     */
    var $m_descTemplate = null;

    /**
     * Descriptor handler.
     * @access protected
     * @var Object
     */
    var $m_descHandler = null;

    /**
     * List of action listeners
     * @access protected
     * @var Array
     */
    var $m_actionListeners = array();

    /**
     * List of trigger listeners
     * @access protected
     * @var Array
     */
    var $m_triggerListeners = array();

    /**
     * List of callback functions to manipulate the record actions
     *
     * @var array
     */
    protected $m_recordActionsCallbacks = array();

    /**
     * List of callback functions to add css class to row.
     * See details in DGList::getRecordlistData() method
     *
     * @var array
     */
    protected $m_rowClassCallback = array();

    /**
     * Tracker variable to see if we are currently in 'modifier mode' (running inside
     * the scope of a modname_nodename_modifier() method). The variable contains the
     * name of the modifying module.
     * @access private
     * @var String
     */
    var $m_modifier = "";

    /**
     * Extended search action. The action which is called if the user
     * wants to perform an extended search.
     *
     * @access private
     * @var String
     */
    var $m_extended_search_action = null;

    /**
     * List of editable list attributes.
     * @access private
     * @var Array
     */
    var $m_editableListAttributes = array();

    /**
     * Multi-record actions, selection mode.
     * @access private
     * @var int
     */
    var $m_mraSelectionMode = MRA_MULTI_SELECT;

    /**
     * The default edit fieldprefix to use for atk
     * @access private
     * @var String
     */
    var $m_edit_fieldprefix = '';

    /**
     * Lock mode.
     *
     * @var int
     */
    private $m_lockMode = 'exclusive'; // Lock::EXCLUSIVE (would mean atkLock needs to be available!)

    /**
     * Default column name (null means across all columns)
     *
     * @var string
     */
    private $m_defaultColumn = null;

    /**
     * Current maximum attribute order value.
     *
     * @var int
     */
    private $m_attribOrder = 0;

    /**
     * Constructor.
     *
     * This initialises the node. Derived classes should always call their
     * parent constructor ($this->Node($name, $flags), to initialize the
     * base class.
     * <br>
     * <b>Example:</b>
     * <code>$this->Node('test',NF_NO_EDIT);</code>
     * @param String $type The nodetype (by default equal to the classname)
     * @param int $flags Bitmask of node flags (NF_*).
     */
    function __construct($type = "", $flags = 0)
    {
        if ($type == "") {
            $type = strtolower(get_class($this));
        }
        $this->m_type = $type;
        $this->m_flags = $flags;
        $this->m_module = Module::getModuleScope();
        Tools::atkdebug("Creating a new node " . $this->m_module . ".$type");

        $this->setEditFieldPrefix(Config::getGlobal('edit_fieldprefix', ''));
    }

    /**
     * Resolve section. If a section is only prefixed by
     * a dot this means we need to add the default tab
     * before the dot.
     *
     * @param string $section section name
     * @return string resolved section name
     */
    function resolveSection($section)
    {
        list($part1, $part2) = (strpos($section, ".") !== false) ? explode('.', $section)
            : array($section, "");
        if ($part2 != null && strlen($part2) > 0 && strlen($part1) == 0) {
            return $this->m_default_tab . "." . $part2;
        } else {
            if (strlen($part2) == 0 && strlen($part1) == 0) {
                return $this->m_default_tab;
            } else {
                return $section;
            }
        }
    }

    /**
     * Resolve sections.
     *
     * @param array $sections section list
     * @return array resolved section list
     *
     * @see resolveSection
     */
    function resolveSections($sections)
    {
        $result = array();

        foreach ($sections as $section) {
            $result[] = $this->resolveSection($section);
        }

        return $result;
    }

    /**
     * Returns the default column name.
     *
     * @return string default column name
     */
    public function getDefaultColumn()
    {
        return $this->m_defaultColumn;
    }

    /**
     * Set default column name.
     *
     * @param string $name default column name
     */
    public function setDefaultColumn($name)
    {
        $this->m_defaultColumn = $name;
    }

    /**
     * Resolve column for sections.
     *
     * If one of the sections contains something after a double
     * colon (:) than that's used as column name, else the default
     * column name will be used.
     *
     * @param array $sections sections
     *
     * @return string column name
     */
    protected function resolveColumn(&$sections)
    {
        $column = $this->getDefaultColumn();

        if (!is_array($sections)) {
            return $column;
        }

        foreach ($sections as &$section) {
            if (strpos($section, ":") !== false) {
                list($section, $column) = explode(':', $section);
            }
        }

        return $column;
    }

    /**
     * Resolve sections, tabs and the order based on the given
     * argument to the attribute add method.
     *
     * @param mixed $sections
     * @param mixed $tabs
     * @param mixed $order
     */
    function resolveSectionsTabsOrder(&$sections, &$tabs, &$column, &$order)
    {
        // Because sections/tabs will probably be used more than the order override option
        // the API for this method now favours the $sections argument. For backwards
        // compatibility we still support the old API ($attribute,$order=0).
        if ($sections !== null && is_int($sections)) {
            $order = $sections;
            $sections = array($this->m_default_tab);
        }

        // If no section/tab is specified or tabs are disabled, we use the current default tab
        // (specified with the setDefaultTab method, or "default" otherwise)
        elseif ($sections === null || (is_string($sections) && strlen($sections) == 0) || !Config::getGlobal("tabs")) {
            $sections = array($this->m_default_tab);
        } // Sections should be an array.
        else {
            if ($sections != "*" && !is_array($sections)) {
                $sections = array($sections);
            }
        }

        $column = $this->resolveColumn($sections);

        if (is_array($sections)) {
            $sections = $this->resolveSections($sections);
        }

        // Filter tabs from section names.
        $tabs = $this->getTabsFromSections($sections);
    }

    /**
     * Add an Attribute (or one of its derivatives) to the node.
     * @param Attribute $attribute The attribute you want to add
     * @param mixed $sections The sections/tab(s) on which the attribute should be
     *                   displayed. Can be a tabname (String) or a list of
     *                   tabs (array) or "*" if the attribute should be
     *                   displayed on all tabs.
     * @param int $order The order at which the attribute should be displayed.
     *                   If ommitted, this defaults to 100 for the first
     *                   attribute, and 100 more for each next attribute that
     *                   is added.
     * @return Attribute the attribute just added
     */
    public function add($attribute, $sections = null, $order = 0)
    {
        $tabs = null;
        $column = null;

        $attribute->m_owner = $this->m_type;

        // If we're running inside modifier scope, we have to tell the attribute
        // what module he originated from.
        if ($this->m_modifier != "") {
            $attribute->m_module = $this->m_modifier;
        }

        if (!Module::atkReadOptimizer()) {
            $this->resolveSectionsTabsOrder($sections, $tabs, $column, $order);

            // check for parent fieldname (treeview)
            if ($attribute->hasFlag(AF_PARENT)) {
                $this->m_parent = $attribute->fieldName();
            }

            // check for cascading delete flag
            if ($attribute->hasFlag(AF_CASCADE_DELETE)) {
                $this->m_cascadingAttribs[] = $attribute->fieldName();
            }

            if ($attribute->hasFlag(AF_HIDE_LIST) && !$attribute->hasFlag(AF_PRIMARY)) {
                if (!in_array($attribute->fieldName(), $this->m_listExcludes)) {
                    $this->m_listExcludes[] = $attribute->fieldName();
                }
            }

            if ($attribute->hasFlag(AF_HIDE_VIEW) && !$attribute->hasFlag(AF_PRIMARY)) {
                if (!in_array($attribute->fieldName(), $this->m_viewExcludes)) {
                    $this->m_viewExcludes[] = $attribute->fieldName();
                }
            }
        } else {
            // when the read optimizer is enabled there is no active tab
            // we circument this by putting all attributes on all tabs
            if ($sections !== null && is_int($sections)) {
                $order = $sections;
            }
            $tabs = "*";
            $sections = "*";
            $column = $this->getDefaultColumn();
        }


        // NOTE: THIS SHOULD WORK. BUT, since add() is called from inside the $this
        // constructor, m_ownerInstance ends up being a copy of $this, rather than
        // a reference. Don't ask me why, it has something to do with the way PHP
        // handles the constructor.
        // To work around this, we reassign the this pointer to the attributes as
        // soon as possible AFTER the constructor. (the dispatcher function)
        $attribute->setOwnerInstance($this);

        if ($attribute->hasFlag(AF_PRIMARY)) {
            if (!in_array($attribute->fieldName(), $this->m_primaryKey)) {
                $this->m_primaryKey[] = $attribute->fieldName();
            }
        }


        $attribute->init();

        $exist = false;
        if (isset($this->m_attribList[$attribute->fieldName()]) && is_object($this->m_attribList[$attribute->fieldName()])) {
            $exist = true;
            // if order is set, overwrite it with new order, last order will count
            if ($order != 0) {
                $this->m_attribIndexList[$this->m_attribList[$attribute->fieldName()]->m_index]["order"] = $order;
            }
            $attribute->m_index = $this->m_attribList[$attribute->fieldName()]->m_index;
        }

        if (!$exist) {
            if ($order == 0) {
                $this->m_attribOrder += 100;
                $order = $this->m_attribOrder;
            }

            if (!Module::atkReadOptimizer()) {
                // add new tab(s) to the tab list ("*" isn't a tab!)
                if ($tabs != "*") {
                    if (!$attribute->hasFlag(AF_HIDE_ADD)) {
                        $this->m_tabList["add"] = isset($this->m_tabList["add"])
                            ? Tools::atk_array_merge($this->m_tabList["add"], $tabs)
                            : $tabs;
                    }
                    if (!$attribute->hasFlag(AF_HIDE_EDIT)) {
                        $this->m_tabList["edit"] = isset($this->m_tabList["edit"])
                            ? Tools::atk_array_merge($this->m_tabList["edit"], $tabs)
                            : $tabs;
                    }
                    if (!$attribute->hasFlag(AF_HIDE_VIEW)) {
                        $this->m_tabList["view"] = isset($this->m_tabList["view"])
                            ? Tools::atk_array_merge($this->m_tabList["view"], $tabs)
                            : $tabs;
                    }
                }

                if ($sections != "*") {
                    if (!$attribute->hasFlag(AF_HIDE_ADD)) {
                        $this->m_sectionList["add"] = isset($this->m_sectionList["add"])
                            ? Tools::atk_array_merge($this->m_sectionList["add"], $sections)
                            : $sections;
                    }
                    if (!$attribute->hasFlag(AF_HIDE_EDIT)) {
                        $this->m_sectionList["edit"] = isset($this->m_sectionList['edit'])
                            ? Tools::atk_array_merge($this->m_sectionList["edit"], $sections)
                            : $sections;
                    }
                    if (!$attribute->hasFlag(AF_HIDE_VIEW)) {
                        $this->m_sectionList["view"] = isset($this->m_sectionList['view'])
                            ? Tools::atk_array_merge($this->m_sectionList["view"], $sections)
                            : $sections;
                    }
                }
            }

            $attribute->m_order = $order;
            $this->m_attribIndexList[] = array(
                "name" => $attribute->fieldName(),
                "tabs" => $tabs,
                "sections" => $sections,
                "order" => $attribute->m_order
            );
            $attribute->m_index = max(array_keys($this->m_attribIndexList)); // might contain gaps
            $attribute->setTabs($tabs);
            $attribute->setSections($sections);
            $this->m_attributeTabs[$attribute->fieldname()] = $tabs;
        }

        // Order the tablist
        $this->m_attribList[$attribute->fieldName()] = &$attribute;
        $attribute->setTabs($this->m_attributeTabs[$attribute->fieldName()]);
        $attribute->setSections($this->m_attribIndexList[$attribute->m_index]['sections']);
        $attribute->setColumn($column);


        return $attribute;
    }

    /**
     * Add fieldset.
     *
     * To include an attribute label use [attribute.label] inside your
     * template. To include an attribute edit/display field use
     * [attribute.field] inside your template.
     *
     * @param string $name name
     * @param string $template template string
     * @param int $flags attribute flags
     * @param mixed $sections The sections/tab(s) on which the attribute should be
     *                   displayed. Can be a tabname (String) or a list of
     *                   tabs (array) or "*" if the attribute should be
     *                   displayed on all tabs.
     * @param int $order The order at which the attribute should be displayed.
     *                   If ommitted, this defaults to 100 for the first
     *                   attribute, and 100 more for each next attribute that
     *                   is added.
     */
    public function addFieldSet($name, $template, $flags = 0, $sections = null, $order = 0)
    {
        $this->add(new FieldSet($name, $template, $flags), $sections, $order);
    }

    /**
     * Retrieve the tabnames from the sections string (tab.section).
     *
     * @param mixed $sections An array with sections or a section string
     * @return array
     */
    function getTabsFromSections($sections)
    {
        if ($sections == "*" || $sections === null) {
            return $sections;
        }

        $tabs = array();

        if (!isset($sections)) {
        } elseif (!is_array($sections)) {
            $sections = array($sections);
        }

        foreach ($sections as $section) {
            $tabs[] = $this->getTabFromSection($section);
        }

        //when using the tab.sections notation, we can have duplicate tabs
        //strip them out.
        return array_unique($tabs);
    }

    /**
     * Strip section part from a section and return the tab.
     *
     * If no tab name is provided, the default tab is returned.
     *
     * @param string $section The section to get the tab from
     */
    function getTabFromSection($section)
    {
        $tab = ($section == null) ? "" : $section;

        if (strstr($tab, ".") !== false) {
            list($tab) = explode(".", $tab);
        }

        return (($tab == "") ? $this->m_default_tab : $tab);
    }

    /**
     * Remove an attribute.
     *
     * Completely removes an attribute from a node.
     * Note: Since other functionality may already depend on the attribute
     * that you are about to remove, it's often better to just hide an
     * attribute if you don't need it.
     * @param String $attribname The name of the attribute to remove.
     */
    function remove($attribname)
    {
        if (is_object($this->m_attribList[$attribname])) {
            Tools::atkdebug("removing attribute $attribname");

            $listindex = $this->m_attribList[$attribname]->m_index;

            unset($this->m_attribList[$attribname]);
            foreach ($this->m_listExcludes as $i => $name) {
                if ($name == $attribname) {
                    unset($this->m_listExcludes[$i]);
                }
            }
            foreach ($this->m_viewExcludes as $i => $name) {
                if ($name == $attribname) {
                    unset($this->m_viewExcludes[$i]);
                }
            }
            foreach ($this->m_cascadingAttribs as $i => $name) {
                if ($name == $attribname) {
                    unset($this->m_cascadingAttribs[$i]);
                    $this->m_cascadingAttribs = array_values($this->m_cascadingAttribs);
                }
            }

            unset($this->m_attribIndexList[$listindex]);
            unset($this->m_attributeTabs[$attribname]);
        }
    }

    /**
     * Returns the table name for this node.
     *
     * @return string table name
     */
    function getTable()
    {
        return $this->m_table;
    }

    /**
     * Get an attribute by name.
     * @param String $name The name of the attribute to retrieve.
     * @return Attribute The attribute.
     */
    function &getAttribute($name)
    {
        $returnValue = isset($this->m_attribList[$name]) ? $this->m_attribList[$name]
            : null;
        return $returnValue;
    }

    /**
     * Checks if the user has filled in something:
     * return true if he has, otherwise return false
     *
     * @param  -
     * @return boolean.
     */
    function &filledInForm()
    {
        if (is_null($this->getAttributes())) {
            return false;
        }

        $postvars = Tools::atkGetPostVar();
        foreach ($this->m_attribList AS $name => $value) {
            if (!$value->hasFlag(AF_HIDE_LIST)) {
                if (!is_array($value->fetchValue($postvars)) && $value->fetchValue($postvars) !== "") {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Gets all the attributes.
     * @return Attribute[] Array with the attributes.
     */
    function &getAttributes()
    {
        if (isset($this->m_attribList)) {
            return $this->m_attribList;
        } else {
            return null;
        }
    }

    /**
     * Returns a list of attribute names.
     *
     * @return array attribute names
     */
    function getAttributeNames()
    {
        return array_keys($this->m_attribList);
    }

    /**
     * Gets the attribute order.
     *
     * @param string $name The name of the attribute
     */
    function getAttributeOrder($name)
    {
        return $this->m_attribIndexList[$this->m_attribList[$name]->m_index]["order"];
    }

    /**
     * Sets an attributes order
     *
     * @param string $name The name of the attribute
     * @param int $order The order of the attribute
     */
    function setAttributeOrder($name, $order)
    {
        $this->m_attribList[$name]->m_order = $order;
        $this->m_attribIndexList[$this->m_attribList[$name]->m_index]["order"] = $order;
    }

    /**
     * Checks if the node has a certain flag set.
     * @param int $flag The flag to check.
     * @return boolean True if the node has the flag.
     */
    function hasFlag($flag)
    {
        return (($this->m_flags & $flag) == $flag);
    }

    /**
     * Add a flag to the node.
     * @param int $flag The flag to add.
     */
    function addFlag($flag)
    {
        $this->m_flags |= $flag;
    }

    /**
     * Removes a flag from the node.
     *
     * @param int $flag The flag to remove from the attribute
     */
    function removeFlag($flag)
    {
        if ($this->hasFlag($flag)) {
            $this->m_flags ^= $flag;
        }
    }

    /**
     * Returns the node flags.
     * @return Integer  node flags
     */
    function getFlags()
    {
        return $this->m_flags;
    }

    /**
     * Set node flags.
     *
     * @param int $flags node flags
     */
    public function setFlags($flags)
    {
        $this->m_flags = $flags;
    }

    /**
     * Returns the current partial name.
     *
     * @return string partial name
     */
    public function getPartial()
    {
        return $this->m_partial;
    }

    /**
     * Is partial request?
     *
     * @return boolean is partial
     */
    function isPartial()
    {
        return $this->m_partial;
    }

    /**
     * Sets the editable list attributes. If you supply this method
     * with one or more string arguments, all arguments are collected in
     * an array. Else the first parameter will be used.
     *
     * @param array $attrs list of attribute names
     */
    function setEditableListAttributes($attrs)
    {
        if (is_array($attrs)) {
            $this->m_editableListAttributes = $attrs;
        } else {
            $this->m_editableListAttributes = func_get_args();
        }
    }

    /**
     * Sets the multi-record-action selection mode. Can either be
     * MRA_MULTI_SELECT (default), MRA_SINGLE_SELECT or
     * MRA_NO_SELECT.
     *
     * @param string $mode selection mode
     */
    function setMRASelectionMode($mode)
    {
        $this->m_mraSelectionMode = $mode;
    }

    /**
     * Returns the multi-record-action selection mode.
     * @return Integer multi-record-action selection mode
     */
    function getMRASelectionMode()
    {
        return $this->m_mraSelectionMode;
    }

    /**
     * Returns the primary key sql expression of a record.
     * @param array $rec The record for which the primary key is calculated.
     * @return String the primary key of the record.
     */
    function primaryKey($rec)
    {
        $primKey = "";
        $nrOfElements = count($this->m_primaryKey);
        for ($i = 0; $i < $nrOfElements; $i++) {
            $p_attrib = $this->m_attribList[$this->m_primaryKey[$i]];
            $primKey .= $this->m_table . "." . $this->m_primaryKey[$i] . "='" . $p_attrib->value2db($rec) . "'";
            if ($i < ($nrOfElements - 1)) {
                $primKey .= " AND ";
            }
        }

        return $primKey;
    }

    /**
     * Retrieve the name of the primary key attribute.
     *
     * Note: If a node has a primary key that consists of multiple attributes,
     * this method will retrieve only the first attribute!
     * @return String First primary key attribute
     */
    function primaryKeyField()
    {
        if (count($this->m_primaryKey) === 0) {
            Tools::atkwarning($this->atkNodeType() . "::primaryKeyField() called, but there are no primary key fields defined!");
            return null;
        }

        return $this->m_primaryKey[0];
    }

    /**
     * Returns a primary key template.
     *
     * Like primaryKey(), this method returns a sql expression, but in this
     * case, no actual data is used. Instead, template fields are inserted
     * into the expression. This is useful for rendering multiple primary
     * keys later with a record and a template parser.
     *
     * @return String Primary key template
     */
    function primaryKeyTpl()
    {
        $primKey = "";
        $nrOfElements = count($this->m_primaryKey);
        for ($i = 0; $i < $nrOfElements; $i++) {
            $primKey .= $this->m_primaryKey[$i] . "='[" . $this->m_primaryKey[$i] . "]'";
            if ($i < ($nrOfElements - 1)) {
                $primKey .= " AND ";
            }
        }
        return $primKey;
    }

    /**
     * Set default sort order for the node.
     * @param String $orderby Default order by. Can be an attribute name or a
     *                        SQL expression.
     */
    function setOrder($orderby)
    {
        $this->m_default_order = $orderby;
    }

    /**
     * Get default sort order for the node.
     * @return String $orderby Default order by. Can be an attribute name or a
     *                        SQL expression.
     */
    function getOrder()
    {
        return str_replace('[table]', $this->getTable(), $this->m_default_order);
    }

    /**
     * Set the table that the node should use.
     *
     * Note: This should be called in the constructor of derived classes,
     * after the base class constructor is called.
     * @param String $tablename The name of the table to use.
     * @param String $seq The name of the sequence to use for autoincrement
     *                    attributes.
     * @param mixed $db The database connection to use. If ommitted, this
     *                  defaults to the default database connection.
     *                  So in apps using only one database, it's not necessary
     *                  to pass this parameter.
     *                  You can pass either a connection (Db instance), or
     *                  a string containing the name of the connection to use.
     */
    function setTable($tablename, $seq = "", $db = null)
    {
        $this->m_table = $tablename;
        if ($seq == "") {
            $seq = $tablename;
        }
        $this->m_seq = $seq;
        $this->m_db = $db;
    }

    /**
     * Sets the database connection.
     *
     * @param string|Db $db database name or object
     */
    public function setDb($db)
    {
        $this->m_db = $db;
    }

    /**
     * Get the database connection for this node.
     * @return Db Database connection instance
     */
    function getDb()
    {
        if ($this->m_db == null) {
            return Tools::atkGetDb();
        } else {
            if (is_object($this->m_db)) {
                return $this->m_db;
            } else {
                // must be a named connection
                return Tools::atkGetDb($this->m_db);
            }
        }
    }

    /**
     * Create an alphabetical index.
     *
     * Any string- or textbased attribute can be used to create an
     * alphabetical index in admin- and selectpages.
     * @param String $attribname The name of the attribute for which to create
     *                           the alphabetical index.
     */
    function setIndex($attribname)
    {
        $this->m_index = $attribname;
    }

    /**
     * Set tab index
     *
     * @param string $tabname Tabname
     * @param int $index Index number
     * @param string $action Action name (add,edit,view)
     */
    function setTabIndex($tabname, $index, $action = "")
    {
        Tools::atkdebug("Node::setTabIndex($tabname,$index,$action)");
        $actionList = array("add", "edit", "view");
        if ($action != "") {
            $actionList = array($action);
        }
        foreach ($actionList as $action) {
            $new_index = $index;
            $list = $this->m_tabList[$action];
            if ($new_index < 0) {
                $new_index = 0;
            }
            if ($new_index > count($list)) {
                $new_index = count($list);
            }
            $current_index = array_search($tabname, $list);
            if ($current_index !== null) {
                $tmp = array_splice($list, $current_index, 1);
                array_splice($list, $new_index, 0, $tmp);
            }
        }
    }

    /**
     * Set default tab being displayed in view/add/edit mode.
     * After calling this method, all attributes which are added after the
     * method call without specification of tab will be placed on the default
     * tab. This means you should use this method before you add any
     * attributes to the node.
     * If you accept the default name for the first tab ("default") you do not
     * need to call this method.
     * @param String $tab the name of the default tab
     */
    function setDefaultTab($tab = "default")
    {
        $this->m_default_tab = $tab;
    }

    /**
     * Get a list of tabs for a certain action.
     * @param String $action The action for which you want to retrieve the
     *                       list of tabs.
     * @return array The list of tabnames.
     *
     */
    function getTabs($action)
    {

        $list = $this->m_tabList[$action];
        $disable = $this->checkTabRights($list);

        if (!is_array($list)) {
            // fallback to view tabs.
            $list = $this->m_tabList["view"];
        }

        // Attributes can also add tabs to the tablist.
        $this->m_filledTabs = array();
        foreach (array_keys($this->m_attribList) as $attribname) {
            $p_attrib = $this->m_attribList[$attribname];
            if ($p_attrib->hasFlag(AF_HIDE)) {
                continue;
            } // attributes to which we don't have access are explicitly hidden


// Only display the attribute if the attribute
            // resides on at least on visible tab
            for ($i = 0, $_i = sizeof($p_attrib->m_tabs); $i < $_i; $i++) {
                if ((is_array($list) && in_array($p_attrib->m_tabs[$i],
                            $list)) || (!is_array($disable) || !in_array($p_attrib->m_tabs[$i], $disable))
                ) {
                    break;
                }
            }

            if (is_object($p_attrib)) {
                $additional = $p_attrib->getAdditionalTabs();
                if (is_array($additional) && count($additional) > 0) {
                    $list = Tools::atk_array_merge($list, $additional);
                    $this->m_filledTabs = Tools::atk_array_merge($this->m_filledTabs, $additional);
                }

                // Keep track of the tabs that containg attribs
                // so we only display none-empty tabs
                $tabCode = $this->m_attributeTabs[$attribname][0];
                if (!in_array($tabCode, $this->m_filledTabs)) {
                    $this->m_filledTabs[] = $tabCode;
                }
            } else {
                Tools::atkdebug("node::getTabs() Warning: $attribname is not an object!?");
            }
        }

        // Check if the currently known tabs all containg attributes
        // so we don't end up with empty tabs
        return $this->checkEmptyTabs($list);
    }

    /**
     * Retrieve the sections for the active tab.
     *
     * @param String $action
     * @return array The active sections.
     */
    function getSections($action)
    {
        $sections = array();

        if (is_array($this->m_sectionList[$action])) {
            foreach ($this->m_sectionList[$action] as $element) {
                list($tab, $sec) = (strpos($element, ".") !== false) ? explode(".", $element)
                    : array($element, null);

                //if this section is on an active tab, we return it.
                if ($tab == $this->getActiveTab() && $sec !== null) {
                    $sections[] = $sec;
                }
            }
        }

        //we do not want duplicate sections on the same tab.
        return array_unique($sections);
    }

    /**
     * Add sections that must be expanded by default.
     *
     */
    function addDefaultExpandedSections()
    {
        $sections = func_get_args();
        $sections = $this->resolveSections($sections);
        $this->m_default_expanded_sections = array_unique(array_merge($sections, $this->m_default_expanded_sections));
    }

    /**
     * Remove sections that must be expanded by default.
     *
     */
    function removeDefaultExpandedSections()
    {
        $sections = func_get_args();

        $this->m_default_expanded_sections = array_diff($this->m_default_expanded_sections, $sections);
    }

    /**
     * Check if the user has the rights to access existing tabs and
     * removes tabs from the list that may not be accessed
     *
     * @param array $tablist Array containing the current tablist
     * @return array with disable tabs
     */
    function checkTabRights(&$tablist)
    {
        global $g_nodes;
        $disable = array();

        if (empty($this->m_module)) {
            return $disable;
        }

        for ($i = 0, $_i = count($tablist); $i < $_i; $i++) {
            if ($tablist[$i] == "" || $tablist[$i] == "default") {
                continue;
            }
            $secMgr = SecurityManager::getInstance();

            // load the $g_nodes array to find out what tabs are required
            if (!isset($g_nodes[$this->m_module][$this->m_module][$this->m_type])) {
                $module = Module::atkGetModule($this->m_module);
                $module->getNodes();
            }

            $priv = "tab_" . $tablist[$i];
            if (isset($g_nodes[$this->m_module][$this->m_module][$this->m_type]) && Tools::atk_in_array($priv,
                    $g_nodes[$this->m_module][$this->m_module][$this->m_type])
            ) {
                // authorisation is required
                if (!$secMgr->allowed($this->m_module . "." . $this->m_type, "tab_" . $tablist[$i])) {
                    Tools::atkdebug("Removing TAB " . $tablist[$i] . " because access to this tab was denied");
                    $disable[] = $tablist[$i];
                    unset($tablist[$i]);
                }
            }
        }

        if (is_array($tablist)) {
            // we might have now something like:
            // [0]=>tabA,[3]=>tabD
            // we convert this to a 'normal' array:
            // [0]=>tabA,[1]=>tabD;
            $newarray = array();
            foreach ($tablist as $tab) {
                $newarray[] = $tab;
            }
            $tablist = $newarray;
        }

        return $disable;
    }

    /**
     * Remove tabs without attribs from the tablist
     * @param array $list The list of tabnames
     * @return array The list of tabnames without the empty tabs.
     *
     */
    function checkEmptyTabs($list)
    {
        $tabList = array();

        if (is_array($list)) {
            foreach ($list AS $tabEntry) {
                if (in_array($tabEntry, $this->m_filledTabs)) {
                    $tabList[] = $tabEntry;
                } else {
                    Tools::atkdebug("Removing TAB " . $tabEntry . " because it had no attributes assigned");
                }
            }
        }

        return $tabList;
    }

    /**
     * Returns the currently active tab.
     *
     * Note that in themes which use dhtml tabs (tabs without reloads), this
     * method will always return the name of the first tab.
     * @return String The name of the currently visible tab.
     */
    function getActiveTab()
    {
        global $ATK_VARS;
        $tablist = $this->getTabs($ATK_VARS["atkaction"]);

        // Note: we may not read atktab from $this->m_postvars, because $this->m_postvars is not filled if this is
        // a nested node (in a relation for example).
        if (!empty($ATK_VARS["atktab"]) && in_array($ATK_VARS["atktab"], $tablist)) {
            $tab = $ATK_VARS["atktab"];
        } elseif (!empty($this->m_default_tab) && in_array($this->m_default_tab, $tablist)) {
            $tab = $this->m_default_tab;
        } else {
            $tab = $tablist[0];
        }
        return $tab;
    }

    /**
     * Get the active sections.
     *
     * @param string $tab The currently active tab
     * @param string $mode The current mode ("edit", "add", etc.)
     * @return array active Sections
     */
    function getActiveSections($tab, $mode)
    {
        $activeSections = array();
        if (is_array($this->m_sectionList[$mode])) {
            foreach ($this->m_sectionList[$mode] as $section) {
                if (substr($section, 0, strlen($tab)) == $tab) {
                    $sectionName = 'section_' . str_replace('.', '_', $section);
                    $key = array(
                        "nodetype" => $this->atknodetype(),
                        "section" => $sectionName
                    );
                    $defaultOpen = in_array($section, $this->m_default_expanded_sections);
                    if (State::get($key, $defaultOpen ? 'opened' : 'closed') != 'closed') {
                        $activeSections[] = $section;
                    }
                }
            }
        }

        return $activeSections;
    }

    /**
     * Add a recordset filter.
     * @param String $filter The fieldname you want to filter OR a SQL where
     *                       clause expression.
     * @param String $value Required value. (Ommit this parameter if you pass
     *                      an SQL expression for $filter.)
     */
    function addFilter($filter, $value = "")
    {
        if ($value == "") {
            // $key is a where clause kind of thing
            $this->m_fuzzyFilters[] = $filter;
        } else {
            // $key is a $key, $value is a value
            $this->m_filters[$filter] = $value;
        }
    }

    /**
     * Search and remove a recordset filter.
     * @param String $filter The filter to search for
     * @param String $value The value to search for in case it is not a fuzzy filter
     * @return TRUE if the given filter was found and removed, FALSE otherwise.
     */
    function removeFilter($filter, $value = "")
    {
        if ($value == "") {
            // fuzzy
            $key = array_search($filter, $this->m_fuzzyFilters);
            if (is_numeric($key)) {
                unset($this->m_fuzzyFilters[$key]);
                $this->m_fuzzyFilters = array_values($this->m_fuzzyFilters);
                return true;
            }
        } else {
            // not fuzzy
            foreach (array_keys($this->m_filters) as $key) {
                if ($filter == $key && $value == $this->m_filters[$key]) {
                    unset($this->m_filters[$key]);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the form buttons for a certain page.
     *
     * Can be overridden by derived classes to define custom buttons.
     * @param String $mode The action for which the buttons are retrieved.
     * @param array $record The record currently displayed/edited in the form.
     *                      This param can be used to define record specific
     *                      buttons.
     * @return array
     */
    function getFormButtons($mode, $record)
    {
        $controller = Controller::getInstance();
        $controller->setNode($this);
        return $controller->getFormButtons($mode, $record);
    }

    /**
     * Generate a box displaying a message that the current record is locked.
     * @return String The HTML fragment containing a box with the message and
     *                a back-button.
     */
    function lockPage()
    {
        $total = null;
        $output = ''; // $this->statusbar();
        $output .= '<img src="' . Config::getGlobal("atkroot") . 'atk/images/lock.gif"><br><br>' . Tools::atktext("lock_locked") . '<br>';
        $output .= '<br><form method="get">' . Tools::session_form(SESSION_BACK) .
            '<input type="submit" class="btn btn-default btn_cancel" value="&lt;&lt; ' . Tools::atktext('back') . '"></form>';

        $ui = $this->getUi();
        if (is_object($ui)) {
            $total = $ui->renderBox(array(
                "title" => $this->actionTitle($this->m_action),
                "content" => $output
            ));
        }
        return $total;
    }

    /**
     * Get the ui instance for drawing and templating purposes.
     *
     * @return Ui An Ui instance for drawing and templating.
     */
    function &getUi()
    {
        return Ui::getInstance();
    }

    /**
     * Generate a title for a certain action on a certain action.
     *
     * The default implementation displayes the action name, and the
     * descriptor of the current record between brackets. This can be
     * overridden by derived classes.
     * @param String $action The action for which the title is generated.
     * @param array $record The record for which the title is generated.
     * @return String The full title of the action.
     */
    function actionTitle($action, $record = "")
    {
        $sm = SessionManager::getInstance();
        $ui = $this->getUi();
        $res = "";

        if ($record != "") {
            $descr = $this->descriptor($record);
            $sm->pageVar("descriptor", $descr);
        }

        $descriptortrace = $sm->descriptorTrace();
        $nomodule = false;
        if (!empty($descriptortrace)) {
            $nomodule = true;
            $descrtrace = "";
            // only show the last 3 elems
            $cnt = count($descriptortrace);
            if ($cnt > 3) {
                $descrtrace = "... - ";
            }
            for ($i = max(0, $cnt - 3), $_i = $cnt; $i < $_i; $i++) {
                $desc = $descriptortrace[$i];
                $descrtrace .= htmlentities($desc, ENT_COMPAT) . " - ";
            }
            $res = $descrtrace . $res;
        }
        if (is_object($ui)) {
            $res .= $ui->nodeTitle($this, $action, $nomodule);
        }

        return $res;
    }

    /**
     * Place a set of tabs around content.
     * @param String $action The action for which the tabs are loaded.
     * @param String $content The content that is to be displayed within the
     *                        tabset.
     * @return String The complete tabset with content.
     */
    function tabulate($action, $content)
    {
        $this->addStyle("sections.css");
        $this->addStyle("tabs.css");
        $list = $this->getTabs($action);
        $sections = $this->getSections($action);
        $tabs = count($list);

        if (count($sections) > 0 || $tabs > 1) {
            $page = $this->getPage();
            $page->register_script(Config::getGlobal("atkroot") . "atk/javascript/dhtml_tabs.js.php?stateful=" . (Config::getGlobal('dhtml_tabs_stateful')
                    ? 1 : 0));

            // Load default tab show script.
            $page->register_loadscript('if ( window.showTab ) {showTab(\'' . (isset($this->m_postvars['atktab'])
                    ? $this->m_postvars['atktab'] : '') . '\');}');

            $fulltabs = $this->buildTabs($action);
            $tabscript = "var tabs = new Array();\n";
            foreach ($fulltabs as $tab) {
                $tabscript .= "tabs[tabs.length] = '" . $tab['tab'] . "';\n";
            }
            $page->register_scriptcode($tabscript);
        }

        if ($tabs > 1) {
            $ui = $this->getUi();
            if (is_object($ui)) {
                return $ui->renderTabs(array(
                    "tabs" => $this->buildTabs($action),
                    "content" => $content
                ));
            }
        }
        return $content;
    }

    /**
     * Determine the default form parameters for an action template.
     * @param boolean $locked If the current record is locked, pass true, so
     *                        the lockicon can be placed in the params too.
     * @return array Default form parameters for action forms (assoc. array)
     */
    function getDefaultActionParams($locked = false)
    {
        $params = $this->getHelp();
        $params["lockstatus"] = $this->getLockStatusIcon($locked);
        $params["formend"] = '</form>';
        return $params;
    }

    /**
     * Check attribute security.
     *
     * Makes some attributes read-only, or hides the attribute based
     * on the current mode / record.
     *
     * @param string $mode current mode (add, edit, view etc.)
     * @param array $record current record (optional)
     */
    function checkAttributeSecurity($mode, $record = null)
    {
        // check if an attribute needs to be read-only or
        // even hidden based on the current record
        $secMgr = SecurityManager::getInstance();
        foreach (array_keys($this->m_attribList) as $attrName) {
            $attr = $this->getAttribute($attrName);

            if (($mode == "add" || $mode == "edit") &&
                !$secMgr->attribAllowed($attr, $mode, $record) &&
                $secMgr->attribAllowed($attr, "view", $record)
            ) {
                $attr->addFlag(AF_READONLY);
            } else {
                if (!$secMgr->attribAllowed($attr, $mode, $record)) {
                    $attr->addFlag(AF_HIDE);
                }
            }
        }
    }

    /**
     * The preAddToEditArray method is called from within the editArray
     * method prior to letting the attributes add themselves to the edit
     * array, but after the edit record values have been collected (a
     * combination of the current record, initial/edit values and the forced
     * values). This makes it possible to do some last-minute modifications to
     * the record data and possibily add some last-minute attributes etc.
     *
     * @param array $record the edit record
     * @param string $mode edit mode (add or edit)
     */
    function preAddToEditArray(&$record, $mode)
    {
        // do nothing
    }

    /**
     * The preAddToViewArray method is called from within the viewArray
     * method prior to letting the attributes add themselves to the view
     * array, but after the view record values have been collected This makes
     * it possible to do some last-minute modifications to the record data
     * and possibily add some last-minute attributes etc.
     *
     * @param array $record the edit record
     * @param string $mode view mode
     */
    function preAddToViewArray(&$record, $mode)
    {
        // do nothing
    }

    /**
     * Function outputs an array with edit fields. For each field the array
     * contains the name, edit HTML code etc. (name, html, obligatory,
     * error, label)
     *
     * @todo The editArray method should use a set of classes to build the
     *       form, instead of an array with an overly complex structure.
     * @param String $mode The edit mode ("add" or "edit")
     * @param array $record The record currently being edited.
     * @param array $forceList A key-value array used to preset certain
     *                            fields to a certain value, regardless of the
     *                            value in the record.
     * @param array $suppressList List of attributenames that you want to hide
     * @param String $fieldprefix Of set, each form element is prefixed with
     *                            the specified prefix (used in embedded form
     *                            fields)
     * @param bool $ignoreTab Ignore the tabs an attribute should be shown on.
     * @param bool $injectSections Inject sections?
     * @return array List of edit fields (per field ( name, html, obligatory,
     *               error, label })
     */
    function editArray(
        $mode = "add",
        $record = null,
        $forceList = "",
        $suppressList = "",
        $fieldprefix = "",
        $ignoreTab = false,
        $injectSections = true
    ) {
        // update visibility of some attributes based on the current record
        $this->checkAttributeSecurity($mode, $record);

        /* read metadata */
        $this->setAttribSizes();

        /* default values */
        if (!empty($record)) {
            $defaults = $record;
        } else {
            $defaults = array();
        }

        $result['hide'] = array();
        $result['fields'] = array();

        /* edit mode */
        if ($mode == "edit") {
            /* nodes can define edit_values */
            $overrides = $this->edit_values($defaults);
            foreach ($overrides as $varname => $value) {
                $defaults[$varname] = $value;
            }
        } /* add mode */ else {
            /* nodes can define initial values, if they don't already have values. */
            if (!isset($defaults['atkerror'])) { // only load initial values the first time (not after an error occured)
                $overrides = $this->initial_values();
                if (is_array($overrides) && count($overrides) > 0) {
                    foreach ($overrides as $varname => $value) {
                        if (!isset($defaults[$varname]) || $defaults[$varname] == "") {
                            $defaults[$varname] = $value;
                        }
                    }
                }
            }
        }

        /* check for forced values */
        if (is_array($forceList)) {
            foreach ($forceList as $forcedvarname => $forcedvalue) {
                $attribname = "";
                if ($forcedvarname != "") {
                    if (strpos($forcedvarname, '.') > 0) {
                        list($firstpart, $field) = explode('.', $forcedvarname);
                        if ($firstpart == $this->m_table) {
                            // this is a filter on the current table.
                            $defaults[$field] = $forcedvalue;
                            $attribname = $field;
                        } else {
                            // this is a filter on a field of another table (something we have a
                            // relationship with.if(is_object($this->m_attribList[$table]))
                            if (is_object($this->m_attribList[$firstpart])) {
                                $defaults[$firstpart][$field] = $forcedvalue;
                                $attribname = $firstpart;
                            } else {
                                // This is not a filter for this node.
                            }
                        }
                    } else {
                        $defaults[$forcedvarname] = $forcedvalue;
                        $attribname = $forcedvarname;
                    }

                    if ($attribname != "") {
                        if (isset($this->m_attribList[$attribname])) {
                            $p_attrib = $this->m_attribList[$attribname];
                            if (is_object($p_attrib) && (!$p_attrib->hasFlag(AF_NO_FILTER))) {
                                $p_attrib->m_flags |= AF_READONLY | AF_HIDE_ADD;
                            }
                        } else {
                            Tools::atkerror("Attribute '$attribname' doesn't exist in the attributelist");
                        }
                    }
                }
            }
        }

        // call preAddToEditArray at the attribute level, allows attribute to do
        // some last minute manipulations on for example the record
        foreach ($this->getAttributes() as $attr) {
            $attr->preAddToEditArray($defaults, $fieldprefix, $mode);
        }

        // call preAddToEditArray for the node itself.
        $this->preAddToEditArray($defaults, $mode);

        // initialize dependencies
        foreach ($this->getAttributes() as $attr) {
            $attr->initDependencies($defaults, $fieldprefix, $mode);
        }

        // extra submission data
        $result["hide"][] = '<input type="hidden" name="atkfieldprefix" value="' . $this->getEditFieldPrefix(false) . '">';
        $result["hide"][] = '<input type="hidden" name="' . $fieldprefix . 'atknodetype" value="' . $this->atknodetype() . '">';
        $result["hide"][] = '<input type="hidden" name="' . $fieldprefix . 'atkprimkey" value="' . Tools::atkArrayNvl($record,
                "atkprimkey", "") . '">';

        /* For all attributes we use the edit() method to get HTML code for editting the
         * attribute's data. If the attribute is hidden we use the hide() method method
         * to get HTML code for hideing the attribute's data. You can override the attribute's
         * edit() method by supplying an <attributename>_edit function in the derived classes.
         */
        $tab = $this->getActiveTab();

        foreach (array_keys($this->m_attribIndexList) as $r) {
            $attribname = $this->m_attribIndexList[$r]["name"];

            $p_attrib = $this->m_attribList[$attribname];
            if ($p_attrib != null) {
                if ($p_attrib->hasDisabledMode(DISABLED_EDIT)) {
                    continue;
                }

                /* fields that have not yet been initialised may be overriden in the url */
                if (!array_key_exists($p_attrib->fieldName(), $defaults) && array_key_exists($p_attrib->fieldName(),
                        $this->m_postvars)
                ) {
                    $defaults[$p_attrib->fieldName()] = $this->m_postvars[$p_attrib->fieldName()];
                }

                /* sometimes a field is hidden although not specified by the field itself */
                $theme = Theme::getInstance();
                if ($theme->getAttribute("tabtype") == "dhtml" || $ignoreTab) {
                    $notOnTab = false;
                } else {
                    $notOnTab = !$p_attrib->showOnTab($tab);
                }

                if ((is_array($suppressList) && count($suppressList) > 0 && in_array($attribname,
                            $suppressList)) || $notOnTab
                ) {
                    $p_attrib->m_flags |= ($mode == "add" ? AF_HIDE_ADD : AF_HIDE_EDIT);
                }

                /* we let the attribute add itself to the edit array */
                $p_attrib->addToEditArray($mode, $result, $defaults, $record['atkerror'], $fieldprefix);
            } else {
                Tools::atkerror("Attribute $attribname not found!");
            }
        }

        if ($injectSections) {
            $this->injectSections($result['fields']);
        }

        /* check for errors */
        $result["error"] = $record['atkerror'];

        /* return the result array */
        return $result;
    }

    /**
     * Function outputs an array with view fields. For each field the array
     * contains the name, view HTML code etc.
     *
     * @todo The viewArray method should use a set of classes to build the
     *       form, instead of an array with an overly complex structure.
     * @param String $mode The edit mode ("view")
     * @param array $record The record currently being viewed.
     * @param bool $injectSections Inject sections?
     * @return array List of edit fields (per field ( name, html, obligatory,
     *               error, label })
     */
    function viewArray($mode, $record, $injectSections = true)
    {
        // update visibility of some attributes based on the current record
        $this->checkAttributeSecurity($mode, $record);

        // call preAddToViewArray at the attribute level, allows attribute to do
        // some last minute manipulations on for example the record
        foreach ($this->getAttributes() as $attr) {
            $attr->preAddToViewArray($record, $mode);
        }

        // call preAddToViewArray for the node itself.
        $this->preAddToViewArray($record, $mode);

        $result = array();

        foreach (array_keys($this->m_attribIndexList) as $r) {
            $attribname = $this->m_attribIndexList[$r]["name"];

            $p_attrib = $this->m_attribList[$attribname];
            if ($p_attrib != null) {
                if ($p_attrib->hasDisabledMode(DISABLED_VIEW)) {
                    continue;
                }

                /* we let the attribute add itself to the view array */
                $p_attrib->addToViewArray($mode, $result, $record);
            } else {
                Tools::atkerror("Attribute $attribname not found!");
            }
        }

        /* inject sections */
        if ($injectSections) {
            $this->injectSections($result['fields']);
        }

        /* return the result array */
        return $result;
    }

    /**
     * Add sections to the edit/view fields array.
     *
     * @param array $fields fields array (will be modified in-place)
     */
    function injectSections(&$fields)
    {
        $this->groupFieldsBySection($fields);

        $addedSections = array();
        $result = array();
        foreach ($fields as $field) {
            /// we add the section link before the first attribute that is in it
            $fieldSections = $field['sections'];
            if (!is_array($fieldSections)) {
                $fieldSections = array($fieldSections);
            }

            $newSections = array_diff($fieldSections, $addedSections);
            if (count($newSections) > 0) {
                foreach ($newSections as $section) {
                    if (strpos($section, '.') !== false) {
                        $result[] = array(
                            "html" => "section",
                            "name" => $section,
                            "tabs" => $field['tabs']
                        );
                        $addedSections[] = $section;
                    }
                }
            }

            $result[] = $field;
        }

        $fields = $result;
    }

    /**
     * Group fields by section.
     *
     * @param array $fields fields array (will be modified in-place)
     */
    function groupFieldsBySection(&$fields)
    {
        $result = array();
        $sections = array();

        // first find sectionless fields and collect all sections
        foreach ($fields as $field) {
            if ($field["sections"] == "*" ||
                (count($field["sections"]) == 1 && $field["sections"][0] == $this->m_default_tab)
            ) {
                $result[] = $field;
            } else {
                if (is_array($field['sections'])) {
                    $sections = array_merge($sections, $field['sections']);
                }
            }
        }

        $sections = array_unique($sections);

        // loop through each section (except the default tab/section) of the mode we are currently in.
        while (count($sections) > 0) {
            $section = array_shift($sections);

            // find fields for this section
            foreach ($fields as $field) {
                if (is_array($field["sections"]) && in_array($section, $field["sections"])) {
                    $result[] = $field;
                }
            }
        }

        $fields = $result;
    }

    /**
     * Retrieve the initial values for a new record.
     *
     * The system calls this method to create a new record. By default
     * this method returns an empty record, but derived nodes may override
     * this method to perform record initialization.
     *
     * @return array Array containing an initial value per attribute.
     *               Only attributes that are initialized appear in the
     *               array.
     */
    function initial_values()
    {
        $record = array();

        foreach (array_keys($this->m_attribList) as $attrName) {
            $attr = $this->getAttribute($attrName);

            if (is_array($this->m_postvars) && isset($this->m_postvars[$attrName])) {
                $value = $attr->fetchValue($this->m_postvars);
            } else {
                $value = $attr->initialValue();
            }

            if ($value !== null) {
                $record[$attr->fieldName()] = $value;
            }
        }

        return $record;
    }

    /**
     * Retrieve new values for an existing record.
     *
     * The system calls this method to override the values of a record
     * before editing the record.
     * The default implementation does not do anything to the record, but
     * derived classes may override this method to make modifications to.
     * the record.
     *
     * @param array $record The record that is about to be edited.
     * @return array The manipulated record.
     */
    function edit_values($record)
    {
        return $record;
    }

    /**
     * Get the template to use for a certain action.
     *
     * The system calls this method to determine which template to use when
     * rendering a certain screen. The default implementation always returns
     * the same template for the same action (it ignores parameter 2 and 3).
     * You can override this method in derived classes however, to determine
     * on the fly which template to use.
     * The action, the current record (if any) and the tab are passed as
     * parameter. By using these params, you can have custom templates per
     * action, and/or per tab, and even per record.
     *
     * @param String $action The action for which you wnat to retrieve the
     *                       template.
     * @param array $record The record for which you want to return the
     *                       template (or NULL if there is no record).
     * @param String $tab The name of the tab for which you want to
     *                       retrieve the template.
     * @return String The filename of the template (without path)
     */
    function getTemplate($action, $record = null, $tab = "")
    {
        switch ($action) {
            case "add": // add and edit both use the same form.
            case "edit":
                return "editform.tpl";
            case "view":
                return "viewform.tpl";
            case "search":
                return "searchform.tpl";
            case "smartsearch":
                return "smartsearchform.tpl";
            case "admin":
                return "recordlist.tpl";
        }
    }

    /**
     * Function outputs a form with all values hidden.
     *
     * This is probably only useful for the atkOneToOneRelation's hide method.
     *
     * @param String $mode The edit mode ("add" or "edit")
     * @param array $record The record that should be hidden.
     * @param array $forceList A key-value array used to preset certain
     *                            fields to a certain value, regardless of the
     *                            value in the record.
     * @param String $fieldprefix Of set, each form element is prefixed with
     *                            the specified prefix (used in embedded form
     *                            fields)
     * @return String HTML fragment containing all hidden elements.
     *
     */
    function hideForm($mode = "add", $record = null, $forceList = "", $fieldprefix = "")
    {
        /* suppress all */
        $suppressList = array();
        foreach (array_keys($this->m_attribIndexList) as $r) {
            $suppressList[] = $this->m_attribIndexList[$r]["name"];
        }

        /* get data, transform into "form", return */
        $data = $this->editArray($mode, $record, $forceList, $suppressList, $fieldprefix);
        $form = '';
        foreach ($data["hide"] as $hide) {
            $form .= $hide;
        }
        return $form;
    }

    /**
     * Builds a list of tabs.
     *
     * This doesn't generate the actual HTML code, but returns the data for
     * the tabs (title, selected, urls that should be loaded upon click of the
     * tab etc).
     * @param String $action The action for which the tabs should be generated.
     * @return array List of tabs
     * @todo Make translation of tabs module aware
     */
    function buildTabs($action = "")
    {
        if ($action == "") {
            // assume active action
            $action = $this->m_action;
        }

        $result = array();

        // which tab is currently selected
        $tab = $this->getActiveTab();

        // build navigator
        $list = $this->getTabs($action);


        if (is_array($list)) {
            $newtab["total"] = count($list);
            foreach ($list as $t) {
                $newtab["title"] = $this->text(array("tab_$t", $t));
                $newtab["tab"] = $t;
                $url = Tools::atkSelf() . "?atknodetype=" . $this->atkNodeType() . "&atkaction=" . $this->m_action . "&atktab=" . $t;
                if ($this->m_action == "view") {
                    $newtab["link"] = SessionManager::sessionUrl($url, SESSION_DEFAULT);
                } else {
                    $newtab["link"] = "javascript:atkSubmit('" . Tools::atkurlencode(SessionManager::sessionUrl($url,
                            SESSION_DEFAULT)) . "')";
                }
                $newtab["selected"] = ($t == $tab);
                $result[] = $newtab;
            }
        }
        return $result;
    }

    /**
     * Retrieve an array with the default actions for a certain mode.
     *
     * This will return a list of actions that can be performed on records
     * of this node in an admin screen.
     * The actions may contain a [pk] template variable to reference a record,
     * so for each record you should run the stringparser on the action.
     *
     * @param String $mode The mode for which you want a list of actions.
     *                     Currently available modes for this method:
     *                     - "admin" (for actions in adminscreens)
     *                     - "relation" (for the list of actions when
     *                       displaying a recordlist in a onetomany-relation)
     *                     - "view" (for actions when viewing only)
     *                     Note: the default implementation of defaultActions
     *                     makes no difference between "relation" and "admin"
     *                     and will return the same actions for both, but you
     *                     might want to override this behaviour in derived
     *                     classes.
     * @param array $params An array of extra parameters to add to all the
     *                      action urls. You can use this to pass things like
     *                      an atkfilter for example. The array should be
     *                      key/value based.
     * @return array List of actions in the form array($action=>$actionurl)
     */
    function defaultActions($mode, $params = array())
    {
        $actions = array();
        $postfix = "";

        if (count($params) > 0) {
            foreach ($params as $key => $value) {
                $postfix .= "&$key=" . rawurlencode($value);
            }
        }

        $actionbase = Tools::atkSelf() . '?atknodetype=' . $this->atknodetype() . '&atkselector=[pk]' . $postfix;
        if (!$this->hasFlag(NF_NO_VIEW) && $this->allowed("view")) {
            $actions["view"] = $actionbase . '&atkaction=view';
        }

        if ($mode != "view") {
            if (!$this->hasFlag(NF_NO_EDIT) && $this->allowed("edit")) {
                $actions["edit"] = $actionbase . '&atkaction=edit';
            }

            if (!$this->hasFlag(NF_NO_DELETE) && $this->allowed("delete")) {
                $actions["delete"] = $actionbase . '&atkaction=delete';
            }
            if ($this->hasFlag(NF_COPY) && $this->allowed("copy")) {
                $actions["copy"] = $actionbase . '&atkaction=copy';
            }
            if ($this->hasFlag(NF_EDITAFTERCOPY) && $this->allowed("editcopy")) {
                $actions["editcopy"] = $actionbase . '&atkaction=editcopy';
            }
        }

        return $actions;
    }

    /**
     * Sets the priority range, for multi-record-priority actions.
     * @param int $min the minimum priority
     * @param int $max the maximum priority (0 for auto => min + record count)
     */
    function setPriorityRange($min = 1, $max = 0)
    {
        $this->m_priority_min = (int)$min;
        if ($max < $this->m_priority_min) {
            $max = 0;
        } else {
            $this->m_priority_max = $max;
        }
    }

    /**
     * Sets the possible multi-record-priority actions.
     * @param array $actions list of actions
     */
    function setPriorityActions($actions)
    {
        if (!is_array($actions)) {
            $this->m_priority_actions = array();
        } else {
            $this->m_priority_actions = $actions;
        }
    }

    /**
     * Get extended search action.
     *
     * @return string extended search action
     */
    function getExtendedSearchAction()
    {
        if (empty($this->m_extended_search_action)) {
            return Config::getGlobal('extended_search_action');
        } else {
            return $this->m_extended_search_action;
        }
    }

    /**
     * Set extended search action.
     *
     * @param string $action extended search action
     */
    function setExtendedSearchAction($action)
    {
        $this->m_extended_search_action = $action;
    }

    /**
     * Function returns a page in which the user is asked if he really wants
     * to perform a certain action.
     * @param mixed $atkselector Selector of current record on which the
     *                           action will be performed (String), or an
     *                           array of selectors when multiple records are
     *                           processed at once. The method uses the
     *                           selector(s) to display the current record(s)
     *                           in the confirmation page.
     * @param String $action The action for which confirmation is needed.
     * @param boolean $locked Pass true if the current record is locked.
     * @param boolean $checkoverride If set to true, this method will try to
     *                               find a custom method named
     *                               "confirm".$action."()" (e.g.
     *                               confirmDelete() and call that method
     *                               instead.
     * @param boolean $mergeSelectors Merge all selectors to one selector string (if more then one)?
     *
     * @return String Complete html fragment containing a box with the
     *                confirmation page, or the output of the custom
     *                override if $checkoverride was true.
     */
    function confirmAction(
        $atkselector,
        $action,
        $locked = false,
        $checkoverride = true,
        $mergeSelectors = true,
        $csrfToken = null
    ) {
        $method = 'confirm' . $action;
        if ($checkoverride && method_exists($this, $method)) {
            return $this->$method($atkselector, $locked);
        }

        $ui = $this->getUi();

        $this->addStyle("style.css");

        if (is_array($atkselector)) {
            $atkselector_str = '((' . implode($atkselector, ') OR (') . '))';
        } else {
            $atkselector_str = $atkselector;
        }

        $formstart = '<form action="' . Tools::atkSelf() . '?"' . SID . ' method="post">';
        $formstart .= Tools::session_form();
        $formstart .= '<input type="hidden" name="atkaction" value="' . $action . '">';
        $formstart .= '<input type="hidden" name="atknodetype" value="' . $this->atknodetype() . '">';


        if (isset($csrfToken)) {
            $this->getHandler($action);
            $formstart .= '<input type="hidden" name="atkcsrftoken" value="' . $csrfToken . '">';
        }

        if ($mergeSelectors) {
            $formstart .= '<input type="hidden" name="atkselector" value="' . $atkselector_str . '">';
        } else {
            if (!is_array($atkselector)) {
                $formstart .= '<input type="hidden" name="atkselector" value="' . $atkselector . '">';
            } else {
                foreach ($atkselector as $selector) {
                    $formstart .= '<input type="hidden" name="atkselector[]" value="' . $selector . '">';
                }
            }
        }

        $buttons = $this->getFormButtons($action, array());
        if (count($buttons) == 0) {
            $buttons[] = '<input name="confirm" type="submit" class="btn btn-default btn_ok atkdefaultbutton" value="' . $this->text('yes') . '">';
            $buttons[] = '<input name="cancel" type="submit" class="btn btn-default btn_cancel" value="' . $this->text('no') . '">';
        }

        $content = "";
        $recs = $this->selectDb($atkselector_str, "", "", "", $this->descriptorFields());
        if (count($recs) == 1) {
            // 1 record, put it in the page title (with the actionTitle call, a few lines below)
            $record = $recs[0];
            $this->getPage()->setTitle(Tools::atktext('app_shorttitle') . " - " . $this->actionTitle($action,
                    $record));
        } else {
            // we are gonna perform an action on more than one record
            // show a list of affected records, at least if we can find a
            // descriptor_def method
            if ($this->m_descTemplate != null || method_exists($this, "descriptor_def")) {
                $record = "";
                $content .= "<ul>";
                for ($i = 0, $_i = count($recs); $i < $_i; $i++) {
                    $content .= "<li>" . str_replace(' ', '&nbsp;', htmlentities($this->descriptor($recs[$i])));
                }
                $content .= "</ul>";
            }
        }

        $content .= '<br>' . $this->confirmActionText($atkselector, $action, true);

        $output = $ui->renderAction($action, array(
            "content" => $content,
            "formstart" => $formstart,
            "formend" => '</form>',
            "buttons" => $buttons
        ));
        return $ui->renderBox(array(
            "title" => $this->actionTitle($action, $record),
            "content" => $output
        ));
    }

    /**
     * Determine the confirmation message.
     * @param String $atkselector The record(s) on which the action is
     *                            performed.
     * @param String $action The action being performed.
     * @param boolean $checkoverride If true, returns the output of a custom
     *                               method named "confirm".$action."text()"
     * @return String The confirmation text.
     */
    function confirmActionText($atkselector = "", $action = "delete", $checkoverride = true)
    {
        $method = 'confirm' . $action . 'text';
        if ($checkoverride && method_exists($this, $method)) {
            return $this->$method($atkselector);
        } else {
            return $this->text("confirm_$action" . (is_array($atkselector) && count($atkselector) > 1
                    ? '_multi' : ''));
        }
    }

    /**
     * Small compare function for sorting attribs on order field
     * @access private
     * @param array $a The first attribute
     * @param array $b The second attribute
     * @return int
     */
    private static function attrib_cmp($a, $b)
    {
        if ($a["order"] == $b["order"]) {
            return 0;
        }
        return ($a["order"] < $b["order"]) ? -1 : 1;
    }

    /**
     * This function initialises certain elements of the node.
     *
     * This must be called right after the constructor. The function has a
     * check to prevent it from being executed twice. If you construct a node
     * using 'new', you have to call this method. If you construct it with the
     * getNode or newNode method, you don't have to call this method.
     */
    function init()
    {
        Tools::atkdebug("init for " . $this->m_type);
        global $g_modifiers;

        // Check if initialisation is not already done.
        if ($this->m_initialised == true) {
            return;
        }

        // We assign the $this reference to the attributes at this stage, since
        // it fails when we do it in the add() function.
        // See also the comments in the add() function.
        foreach (array_keys($this->m_attribList) as $attribname) {
            $p_attrib = $this->m_attribList[$attribname];
            $p_attrib->setOwnerInstance($this);
        }

        // See if there are modules active that modify this node, and apply the
        // modifiers if found.
        if (isset($g_modifiers[$this->atknodetype()])) {
            foreach ($g_modifiers[$this->atknodetype()] as $modulename) {
                $module = Module::atkGetModule($modulename);
                $module->modifier($this);
            }
        }

        $this->_addListeners();

        // We set the tabs for the attributes
        foreach (array_keys($this->m_attribList) as $attribname) {
            $p_attrib = $this->m_attribList[$attribname];
            $p_attrib->setTabs($this->m_attributeTabs[$attribname]);
        }

        $this->attribSort();

        $this->setAttribSizes();

        $lockType = Config::getGlobal("lock_type");
        if (!empty($lockType) && $this->hasFlag(NF_LOCK)) {
            $this->m_lock = Lock::getInstance();
        } else {
            $this->removeFlag(NF_LOCK);
        }


        $this->m_initialised = true;

        // Call the attributes postInit method to do some last time
        // initialization if necessary.
        foreach (array_keys($this->m_attribList) as $attribname) {
            $p_attrib = $this->m_attribList[$attribname];
            $p_attrib->postInit();
        }
    }

    /**
     * Add the listeners for the current node
     * A listener can be defined either by placing an instantiated object
     * or the full location in Tools::atkimport( style notation, in a global array
     * called $g_nodeListeners (useful for example for adding listeners
     * to nodes from another module's module.inc file. in module.inc files,
     * $listeners can be used to add listeners to a node.
     * @access private
     */
    function _addListeners()
    {
        global $g_nodeListeners;
        if (isset($g_nodeListeners[$this->atknodetype()])) {
            foreach ($g_nodeListeners[$this->atknodetype()] as $listener) {
                if (is_object($listener)) {
                    $this->addListener($listener);
                } else {
                    if (is_string($listener)) {
                        $listenerobj = new $listener();
                        if (is_object($listenerobj)) {
                            $this->addListener($listenerobj);
                        } else {
                            Tools::atkdebug("We couldn't find a classname for listener with supposed nodetype: '$listener'");
                        }
                    } else {
                        Tools::atkdebug("Failed to add listener with supposed nodetype: '$listener'");
                    }
                }
            }
        }
    }

    /**
     * This function reads meta information from the database and initialises
     * its attributes with the metadata.
     *
     * This method should be called before rendering a form, if you want the
     * sizes of all the inputs to match the fieldlengths from the database.
     */
    function setAttribSizes()
    {
        if ($this->m_attribsizesset) {
            return true;
        }

        $db = $this->getDb();
        $metainfo = $db->tableMeta($this->m_table);

        foreach (array_keys($this->m_attribList) as $attribname) {
            $p_attrib = $this->m_attribList[$attribname];
            if (is_object($p_attrib)) {
                $p_attrib->fetchMeta($metainfo);
            }
        }
        $this->m_attribsizesset = true;
    }

    /**
     * This is the wrapper method for all http requests on a node.
     *
     * The method looks at the atkaction from the postvars and determines what
     * should be done. If possible, it instantiates actionHandlers for
     * handling the actual action.
     *
     * @param array $postvars The request variables for the node.
     * @param int $flags Render flags (see class Page).
     */
    function dispatch($postvars, $flags = null)
    {
        Tools::atkdebug("Node::dispatch()");
        $controller = Controller::getInstance();
        $controller->setNode($this);
        return $controller->handleRequest($postvars, $flags);
    }

    /**
     * Render a generic page, with a box, title, stacktrace etc.
     * @param String $title The pagetitle and if $content is a string, also
     *                      the boxtitle.
     * @param mixed $content The content to display on the page. This can be:
     *                       - A string which will be the content of a single
     *                         box on the page.
     *                       - An associative array of $boxtitle=>$boxcontent
     *                         pairs. Each pair will be rendered as a seperate
     *                         box.
     * @return String A complete html page with the desired content.
     */
    function genericPage($title, $content)
    {
        $controller = Controller::getInstance();
        $controller->setNode($this);
        return $controller->genericPage($title, $content);
    }

    /**
     * Render a generic action.
     *
     * Renders actionpage.tpl for the desired action. This includes the
     * given block(s) and a pagetrial, but not a box.
     * @param String $action The action for which the page is rendered.
     * @param mixed $blocks Pieces of html content to be rendered. Can be a
     *                      single string with content, or an array with
     *                      multiple content blocks.
     * @return String Piece of HTML containing the given blocks and a pagetrail.
     */
    function renderActionPage($action, $blocks = array())
    {
        $controller = Controller::getInstance();
        $controller->setNode($this);
        return $controller->renderActionPage($action, $blocks);
    }

    /**
     * Use this function to enable feedback for one or more actions.
     *
     * When feedback is enabled, the action does not immediately return to the
     * previous screen, but first displays a message to the user. (e.g. 'The
     * record has been saved').
     *
     * @param mixed $action The action for which feedback is enabled. You can
     *                      either pass one action or an array of actions.
     * @param int $statusmask The status(ses) for which feedback is enabled.
     *                        If for example this is set to ACTION_FAILED,
     *                        feedback is enabled only when the specified
     *                        action failed. It is possible to specify more
     *                        than one status by concatenating with '|'.
     */
    function setFeedback($action, $statusmask)
    {
        if (is_array($action)) {
            for ($i = 0, $_i = count($action); $i < $_i; $i++) {
                $this->m_feedback[$action[$i]] = $statusmask;
            }
        } else {
            $this->m_feedback[$action] = $statusmask;
        }
    }

    /**
     * Get the page instance of the page on which the node can render output.
     * @return Page The page instance.
     */
    function &getPage()
    {
        $page = Page::getInstance();
        return $page;
    }

    /**
     * Returns a new page builder instance.
     *
     * @return PageBuilder
     */
    public function createPageBuilder()
    {
        return new PageBuilder($this);
    }

    /**
     * Redirect the browser to a different location.
     *
     * This is usually used at the end of actions that have no output. An
     * example: when the user clicks 'save and close' in an edit screen, the
     * action 'save' is executed. If the save is succesful, this method is
     * called to redirect the user back to the adminpage.
     * When $config_debug is set to 2, redirects are paused and you can click
     * a link to execute the redirect (useful for debugging the action that
     * called the redirect).
     * Note: this method should be called before any output has been sent to
     * the browser, i.e. before any echo or before the call to
     * Output::outputFlush().
     *
     * @static
     * @param String $location The url to which you want to redirect the user.
     *                         If ommitted, the call automatically redirects
     *                         to the previous screen of the user. (one level
     *                         back on the session stack).
     * @param array $recordOrExit If you pass a record here, the record is passed
     *                            as 'atkpkret' to the redirected url. Usually it's
     *                            not necessary to pass this parameter. If you pass a
     *                            boolean here we assume it's value must be used for
     *                            the exit parameter.
     * @param boolean $exit Exit script after redirect.
     * @param int $levelskip Number of levels to skip
     */
    public function redirect($location = "", $recordOrExit = array(), $exit = false, $levelskip = 1)
    {
        global $g_returnurl;

        Tools::atkdebug("node::redirect()");

        $record = $recordOrExit;
        if (is_bool($recordOrExit)) {
            $record = array();
            $exit = $recordOrExit;
        }

        if ($g_returnurl != "") {
            $location = $g_returnurl;
        }

        if ($location == "") {
            $location = SessionManager::sessionUrl(Tools::atkSelf(), SESSION_BACK, $levelskip);
        }

        if (count($record)) {
            if (isset($this->m_postvars["atkpkret"])) {
                $location .= "&" . $this->m_postvars["atkpkret"] . "=" . rawurlencode($this->primaryKey($record));
            }
        }

        // The actual redirect.
        if (Config::getGlobal("debug") >= 2) {
            $debugger = Debugger::getInstance();
            $debugger->setRedirectUrl($location);
            Tools::atkdebug('Non-debug version would have redirected to <a href="' . $location . '">' . $location . '</a>');
            if ($exit) {
                $output = Output::getInstance();
                $output->outputFlush();
                exit();
            }
        } else {
            Tools::atkdebug('redirecting to: ' . $location);

            if (substr($location, -1) == "&") {
                $location = substr($location, 0, -1);
            }
            if (substr($location, -1) == "?") {
                $location = substr($location, 0, -1);
            }

            global $g_error_msg;
            if (count($g_error_msg) > 0) {
                Tools::mailreport();
            }

            header('Location: ' . $location);
            if ($exit) {
                exit();
            }
        }
    }

    /**
     * Parse a set of url vars into a valid record structure.
     *
     * When attributes are posted in a formposting, the values may not be
     * valid yet. After posting, a call to updateRecord should be made to
     * translate the html values into the internal values that the attributes
     * work with.
     * @param array $vars The request variables that were posted from a form.
     * @param array $includes Only fetch the value for these attributes.
     * @param array $excludes Don't fetch the value for these attributes.
     * @param array $postedOnly Only fetch the value for attributes that have really been posted.
     * @return array A valid record.
     */
    function updateRecord($vars = "", $includes = null, $excludes = null, $postedOnly = false)
    {
        if ($vars == "") {
            $vars = $this->m_postvars;
        }
        $record = array();

        foreach (array_keys($this->m_attribList) as $attribname) {
            if ((!is_array($includes) || in_array($attribname, $includes)) &&
                (!is_array($excludes) || !in_array($attribname, $excludes))
            ) {
                $p_attrib = $this->m_attribList[$attribname];
                if (!$postedOnly || $p_attrib->isPosted($vars)) {
                    $record[$p_attrib->fieldName()] = $p_attrib->fetchValue($vars);
                }
            }
        }

        if (isset($vars['atkprimkey'])) {
            $record["atkprimkey"] = $vars["atkprimkey"];
        }

        return $record;
    }

    /**
     * Update a record with variables from a form posting.
     *
     * Similar to updateRecord(), but here you can pass an existing record
     * (for example loaded from the db), and update it with the the variables
     * from the request. Instead of returning a record, the record you pass
     * is modified directly.
     *
     * @param array $record The record to update.
     * @param array $vars The request variables that were posted from a form.
     */
    function modifyRecord(&$record, $vars)
    {
        foreach (array_keys($this->m_attribList) as $attribname) {
            $p_attrib = $this->m_attribList[$attribname];
            $record[$p_attrib->fieldName()] = $p_attrib->fetchValue($vars);
        }
    }

    /**
     * Get descriptor handler.
     * @return Object descriptor handler
     */
    function &getDescriptorHandler()
    {
        return $this->m_descHandler;
    }

    /**
     * Set descriptor handler.
     * @param Object $handler The descriptor handler.
     */
    function setDescriptorHandler(&$handler)
    {
        $this->m_descHandler = &$handler;
    }

    /**
     * Returns the descriptor template for this node.
     * @return String The descriptor Template
     */
    function getDescriptorTemplate()
    {
        return $this->m_descTemplate;
    }

    /**
     * Sets the descriptor template for this node.
     * @param String $template The descriptor template.
     */
    function setDescriptorTemplate($template)
    {
        $this->m_descTemplate = $template;
    }

    /**
     * Retrieve the list of attributes that are used in the descriptor
     * definition.
     * @return array The names of the attributes forming the descriptor.
     */
    function descriptorFields()
    {
        $fields = array();

        // See if node has a custom descriptor definition.
        if ($this->m_descTemplate != null || method_exists($this, "descriptor_def")) {
            if ($this->m_descTemplate != null) {
                $descriptordef = $this->m_descTemplate;
            } else {
                $descriptordef = $this->descriptor_def();
            }

            // parse fields from descriptordef
            $parser = new StringParser($descriptordef);
            $fields = $parser->getFields();

            // There might be fields that have a '.' in them. These fields are
            // a concatenation of an attributename (probably a relation), and a subfield
            // (a field of the destination node).
            // The actual field is the one in front of the '.'.
            for ($i = 0, $_i = count($fields); $i < $_i; $i++) {
                $elems = explode(".", $fields[$i]);
                if (count($elems) > 1) {
                    // dot found. attribute is the first item.
                    $fields[$i] = $elems[0];
                }
            }
        } else {
            // default descriptor.. (default is first attribute of a node)
            $keys = array_keys($this->m_attribList);
            $fields[0] = $keys[0];
        }
        return $fields;
    }

    /**
     * Determine a descriptor of a record.
     *
     * The descriptor is a string that describes a record for the user. For
     * person records, this may be the firstname and the lastname, for
     * companies it may be the company name plus the city etc.
     * The descriptor is used when displaying records in a dropdown for
     * example, or in the title of editpages, delete confirmations etc.
     *
     * The descriptor method calls a method named descriptor_def() on the node
     * to retrieve a template for the descriptor (string with attributenames
     * between blockquotes, for example "[lastname], [firstname]".
     *
     * If the node has no descriptor_def() method, the first attribute of the
     * node is used as descriptor.
     *
     * Derived classes may override this method to implement custom descriptor
     * logic.
     *
     * @param array $rec The record for which the descriptor is returned.
     * @return String The descriptor for the record.
     */
    function descriptor($rec = "")
    {
        // Descriptor handler is set?
        if ($this->m_descHandler != null) {
            return $this->m_descHandler->descriptor($rec, $this);
        }

        // Descriptor template is set?
        if ($this->m_descTemplate != null) {
            $parser = new StringParser($this->m_descTemplate);
            return $parser->parse($rec);
        } // See if node has a custom descriptor definition.
        else {
            if (method_exists($this, "descriptor_def")) {
                $parser = new StringParser($this->descriptor_def());
                return $parser->parse($rec);
            } else {
                // default descriptor.. (default is first attribute of a node)
                $keys = array_keys($this->m_attribList);
                return $rec[$keys[0]];
            }
        }
    }

    /**
     * Sets the lock mode.
     *
     * @param int $lockMode lock mode (Lock::EXCLUSIVE, Lock::SHARED)
     */
    public function setLockMode($lockMode)
    {
        $this->m_lockMode = $lockMode;
    }

    /**
     * Returns the lock mode.
     *
     * @return int lock mode (Lock::EXCLUSIVE, Lock::SHARED)
     */
    public function getLockMode()
    {
        return $this->m_lockMode;
    }

    /**
     * Validates a record.
     *
     * Validates unique fields, required fields, dataformat etc.
     *
     * @internal This method instantiates the node's validator object, and
     *           delegates validation to that object.
     *
     * @param array $record The record to validate
     * @param String $mode The mode for which validation is performed ('add' or 'update')
     * @param array $ignoreList The list of attributes that should not be
     *                         validated
     */
    function validate(&$record, $mode, $ignoreList = array())
    {
        $validateObj = new $this->m_validate_class();

        $validateObj->setNode($this);
        $validateObj->setRecord($record);
        $validateObj->setIgnoreList($ignoreList);
        $validateObj->setMode($mode);

        return $validateObj->validate();
    }

    /**
     * Add a unique field set.
     *
     * When you add a set of attributes using this method, any combination of
     * values for the attributes should be unique. For example, if you pass
     * array("name", "parent_id"), name does not have to be unique, parent_id
     * does not have to be unique, but the combination should be unique.
     *
     * @param array $fieldArr The list of names of attributes that should be
     *                        unique in combination.
     */
    function addUniqueFieldset($fieldArr)
    {
        sort($fieldArr);
        if (!in_array($fieldArr, $this->m_uniqueFieldSets)) {
            $this->m_uniqueFieldSets[] = $fieldArr;
        }
    }

    /**
     * Called by updateDb to load the original record inside the record if the
     * NF_TRACK_CHANGES flag is set.
     *
     * NOTE: this method is made public because it's called from the update handler
     *
     * @param array $record
     * @param array $excludes
     * @param array $includes
     */
    public function trackChangesIfNeeded(&$record, $excludes = '', $includes = '')
    {
        if (!$this->hasFlag(NF_TRACK_CHANGES) || isset($record['atkorgrec'])) {
            return;
        }

        // We need to add the NO_FILTER flag in case the new values would filter the record.
        $flags = $this->m_flags;

        $this->addFlag(NF_NO_FILTER);

        $record["atkorgrec"] = $this->select()
            ->where($record['atkprimkey'])
            ->excludes($excludes)
            ->includes($includes)
            ->mode('edit')
            ->firstRow();

        // Need to restore the NO_FILTER bit back to its original value.
        $this->m_flags = $flags;
    }

    /**
     * Update a record in the database.
     *
     * The record should already exist in the database, or this method will
     * fail.
     *
     * NOTE: Does not commit your transaction! If you are using a database that uses
     * transactions you will need to call 'Tools::atkGetDb()->commit()' manually.
     *
     * @param array $record The record to update in the database.
     * @param bool $exectrigger wether to execute the pre/post update triggers
     * @param array $excludes exclude list (these attribute will *not* be updated)
     * @param array $includes include list (only these attributes will be updated)
     * @return boolean True if succesful, false if not.
     */
    function updateDb(&$record, $exectrigger = true, $excludes = "", $includes = "")
    {
        $db = $this->getDb();
        $query = &$db->createQuery();

        $query->addTable($this->m_table);

        // The record that must be updated is indicated by 'atkprimkey'
        // (not by atkselector, since the primary key might have
        // changed, so we use the atkprimkey, which is the value before
        // any update happened.)
        if ($record['atkprimkey'] != "") {
            $this->trackChangesIfNeeded($record, $excludes, $includes);

            if ($exectrigger) {
                $this->executeTrigger("preUpdate", $record);
            }

            $pk = $record['atkprimkey'];
            $query->addCondition($pk);

            $storelist = array("pre" => array(), "post" => array(), "query" => array());

            foreach (array_keys($this->m_attribList) as $attribname) {
                if ((!is_array($excludes) || !in_array($attribname, $excludes)) &&
                    (!is_array($includes) || in_array($attribname, $includes))
                ) {
                    $p_attrib = $this->m_attribList[$attribname];
                    if ($p_attrib->needsUpdate($record) || Tools::atk_in_array($attribname, $includes)) {
                        $storemode = $p_attrib->storageType("update");
                        if (Tools::hasFlag($storemode, PRESTORE)) {
                            $storelist["pre"][] = $attribname;
                        }
                        if (Tools::hasFlag($storemode, POSTSTORE)) {
                            $storelist["post"][] = $attribname;
                        }
                        if (Tools::hasFlag($storemode, ADDTOQUERY)) {
                            $storelist["query"][] = $attribname;
                        }
                    }
                }
            }

            if (!$this->_storeAttributes($storelist["pre"], $record, "update")) {
                return false;
            }

            for ($i = 0, $_i = count($storelist["query"]); $i < $_i; $i++) {
                $p_attrib = $this->m_attribList[$storelist["query"][$i]];
                $p_attrib->addToQuery($query, $this->m_table, "", $record, 1, "update"); // start at level 1
            }

            if (count($query->m_fields) && !$query->executeUpdate()) {
                return false;
            }


            if (!$this->_storeAttributes($storelist["post"], $record, "update")) {
                return false;
            }

            // Now we call a postUpdate function, that can be used to do some processing after the record
            // has been saved.
            if ($exectrigger) {
                return $this->executeTrigger("postUpdate", $record);
            } else {
                return true;
            }
        } else {
            Tools::atkdebug("NOT UPDATING! NO SELECTOR SET!");
            return false;
        }
        return true;
    }

    /**
     * Call the store() method on a list of attributes.
     * @access private
     * @param array $storelist The list of attributes for which the
     *                         store() method should be called.
     * @param array $record The master record being stored.
     * @param String $mode The storage mode ("add", "copy" or "update")
     * @return boolean True if succesful, false if not.
     */
    function _storeAttributes($storelist, &$record, $mode)
    {
        // store special storage attributes.
        for ($i = 0, $_i = count($storelist); $i < $_i; $i++) {
            $p_attrib = $this->m_attribList[$storelist[$i]];
            if (!$p_attrib->store($this->getDb(), $record, $mode)) {
                // something went wrong.
                Tools::atkdebug("Store aborted. Attribute '" . $storelist[$i] . "' reported an error.");
                return false;
            }
        }
        return true;
    }

    /**
     * Copy a record in the database.
     *
     * Primarykeys are automatically regenerated for the copied record. Any
     * detail records (onetomanyrelation) are copied too. Refered records
     * manytoonerelation) are not copied.
     *
     * @param array $record The record to copy.
     * @param string $mode The mode we're in (mostly "copy")
     * @return boolean True if succesful, false if not.
     */
    function copyDb(&$record, $mode = "copy")
    {
        // add original record
        $original = $record; // force copy
        $record['atkorgrec'] = $original;

        //notify precopy listeners
        $this->preNotify("precopy", $record);

        // remove primarykey (copied record will get a new primary key)
        unset($record["atkprimkey"]);

        // remove trigger has been executed references
        foreach (array_keys($record) as $key) {
            if (preg_match('/^__executed.*$/', $key)) {
                unset($record[$key]);
            }
        }

        $this->preCopy($record);
        return $this->addDb($record, true, $mode);
    }

    /**
     * Get the current searchmode.
     *
     * @return mixed If there is one searchmode set for all attributes, this
     *               method returns a string. If there are searchmodes per
     *               attribute, an array of strings is returned.
     */
    function getSearchMode()
    {
        //The searchmode of an index should be used only once, therefore it uses
        // atksinglesearchmode instead of atksearchmode.
        if (isset($this->m_postvars["atksinglesearchmode"])) {
            return $this->m_postvars["atksinglesearchmode"];
        } else {
            if (isset($this->m_postvars["atksearchmode"])) {
                return $this->m_postvars["atksearchmode"];
            }
        }
        return Config::getGlobal("search_defaultmode");
    }

    /**
     * Set some default for the selector.
     *
     * @param Selector $selector selector
     * @param string $condition condition
     * @param array $params condition bind parameters
     */
    protected function _initSelector(Selector $selector, $condition = null, $params = array())
    {
        $selector->orderBy($this->getOrder());
        $selector->ignoreDefaultFilters($this->hasFlag(NF_NO_FILTER));
        $selector->ignorePostvars(Module::atkReadOptimizer());

        if ($condition != null) {
            $selector->where($condition, $params);
        }
    }

    /**
     * Retrieve records from the database using a handy helper class.
     *
     * @param string $condition condition
     * @param array $params condition bind parameters
     *
     * @return Selector
     */
    public function select($condition = null, array $params = array())
    {
        $class = "Sintattica\\Atk\\Utils\\Selector";

        if (method_exists($this, 'selectDb') || method_exists($this, 'countDb')) {
            $class = "Sintattica\\Atk\\Utils\\CompatSelector";
        }

        $selector = new $class($this);
        $this->_initSelector($selector, $condition, $params);

        return $selector;
    }

    /**
     * Returns a record (array) as identified by a primary key (usually an "id" column),
     * including applicable relations.
     *
     * @param int $pk primary key identifying the record
     * @return array the associated record, or null if no such record exists
     */
    public function fetchByPk($pk)
    {
        return $this->select($this->getTable() . "." . $this->primaryKeyField() . '= ?', array($pk))->firstRow();
    }

    /**
     * Add this node to an existing query.
     *
     * Framework method, it should not be necessary to call this method
     * directly.
     * This method is used when adding the entire node to an existing
     * query, as part of a join.
     * @todo The allfields parameter is too inflexible.
     * @param Query $query The query statement
     * @param String $alias The aliasprefix to use for fields from this node
     * @param int $level The recursion level.
     * @param boolean $allfields If set to true, all fields from the node are
     *                           added to the query. If set to false, only
     *                           the primary key and fields from the desriptor
     *                           are added.
     * @param string $mode The mode we're in
     * @param array $includes List of fields that should be included
     */
    function addToQuery(&$query, $alias = "", $level = 0, $allfields = false, $mode = "select", $includes = array())
    {
        if ($level >= 4) {
            return;
        }

        $usefieldalias = false;

        if ($alias == "") {
            $alias = $this->m_table;
        } else {
            $usefieldalias = true;
        }

        // If allfields is set, we load the entire record.. otherwise, we only
        // load the important fields (descriptor and primary key fields)
        // this is mainly used by onetoonerelation.
        if ($allfields) {
            $usedFields = array_keys($this->m_attribList);
        } else {
            $usedFields = Tools::atk_array_merge($this->descriptorFields(), $this->m_primaryKey, $includes);
            foreach (array_keys($this->m_attribList) as $name) {
                if (is_object($this->m_attribList[$name]) && $this->m_attribList[$name]->hasFlag(AF_FORCE_LOAD)) {
                    $usedFields[] = $name;
                }
            }
            $usedFields = array_unique($usedFields);
        }

        foreach ($usedFields as $usedfield) {
            list($attribname) = explode(".", $usedfield);
            $p_attrib = $this->m_attribList[$attribname];
            if (is_object($p_attrib)) {
                $loadmode = $p_attrib->loadType("");

                if ($loadmode && Tools::hasFlag($loadmode, ADDTOQUERY)) {
                    if ($usefieldalias) {
                        $fieldaliasprefix = $alias . "_AE_";
                    }

                    $dummy = array();
                    $p_attrib->addToQuery($query, $alias, $fieldaliasprefix, $dummy, $level, $mode);
                }
            } else {
                Tools::atkdebug("$attribname is not an object?! Check your descriptor_def for non-existant fields");
            }
        }
    }

    /**
     * Get search condition for this node.
     *
     * @param Query $query
     * @param string $table
     * @param string $alias
     * @param string $value
     * @param string $searchmode
     * @return string The search condition
     */
    function getSearchCondition(&$query, $table, $alias, $value, $searchmode)
    {
        $usefieldalias = false;

        if ($alias == "") {
            $alias = $this->m_table;
        } else {
            $usefieldalias = true;
        }

        $searchConditions = array();

        $attribs = $this->descriptorFields();
        array_unique($attribs);

        foreach ($attribs as $field) {
            $p_attrib = $this->getAttribute($field);
            if (!is_object($p_attrib)) {
                continue;
            }

            if ($usefieldalias) {
                $fieldaliasprefix = $alias . "_AE_";
            }

            // check if the node has a searchcondition method defined for this attr
            $methodName = $field . '_searchcondition';
            if (method_exists($this, $methodName)) {
                $searchCondition = $this->$methodName($query, $table, $value, $searchmode);
                if ($searchCondition != "") {
                    $searchConditions[] = $searchCondition;
                }
            } else {
                // checking for the getSearchCondition for backwards compatibility
                if (method_exists($p_attrib, "getSearchCondition")) {
                    $attribsearchmode = $searchmode;
                    if (is_array($searchmode)) {
                        $attribsearchmode = $attribsearchmode[$p_attrib->m_name];
                    }
                    Tools::atkdebug("getSearchCondition: $table - $fieldaliasprefix");
                    $searchCondition = $p_attrib->getSearchCondition($query, $table, $value, $searchmode,
                        $fieldaliasprefix);
                    if ($searchCondition != "") {
                        $searchConditions[] = $searchCondition;
                    }
                } else {
                    // if the attrib can't return it's searchcondition, we'll just add it to the query
                    // and hope for the best
                    $p_attrib->searchCondition($query, $table, $value, $searchmode, $fieldaliasprefix);
                }
            }
        }

        if (count($searchConditions)) {
            return "(" . implode(" OR ", $searchConditions) . ")";
        } else {
            return "";
        }
    }

    /**
     * Save a new record to the database.
     *
     * The record is passed by reference, because any autoincrement field gets
     * its value when stored to the database. The record is updated, so after
     * the call to addDb you can use access the primary key fields.
     *
     * NOTE: Does not commit your transaction! If you are using a database that uses
     * transactions you will need to call 'Tools::atkGetDb()->commit()' manually.
     *
     * @param array $record The record to save.
     * @param boolean $exectrigger Indicates whether the postAdd trigger
     *                             should be fired.
     * @param string $mode The mode we're in
     * @param array $excludelist List of attributenames that should be ignored
     *                           and not stored in the database.
     * @return boolean True if succesful, false if not.
     */
    function addDb(&$record, $exectrigger = true, $mode = "add", $excludelist = array())
    {
        if ($exectrigger) {
            if (!$this->executeTrigger("preAdd", $record, $mode)) {
                Tools::atkerror("preAdd() failed!");
                return false;
            }
        }

        $db = $this->getDb();
        $query = $db->createQuery();

        $storelist = array("pre" => array(), "post" => array(), "query" => array());

        $query->addTable($this->m_table);

        foreach (array_keys($this->m_attribList) as $attribname) {
            $p_attrib = $this->m_attribList[$attribname];
            if (!Tools::atk_in_array($attribname,
                    $excludelist) && ($mode != "add" || $p_attrib->needsInsert($record))
            ) {
                $storemode = $p_attrib->storageType($mode);
                if (Tools::hasFlag($storemode, PRESTORE)) {
                    $storelist["pre"][] = $attribname;
                }
                if (Tools::hasFlag($storemode, POSTSTORE)) {
                    $storelist["post"][] = $attribname;
                }
                if (Tools::hasFlag($storemode, ADDTOQUERY)) {
                    $storelist["query"][] = $attribname;
                }
            }
        }

        if (!$this->_storeAttributes($storelist["pre"], $record, $mode)) {
            return false;
        }

        for ($i = 0, $_i = count($storelist["query"]); $i < $_i; $i++) {
            $p_attrib = $this->m_attribList[$storelist["query"][$i]];
            $p_attrib->addToQuery($query, $this->m_table, "", $record, 1, 'add'); // start at level 1
        }

        if (!$query->executeInsert()) {
            Tools::atkdebug("executeInsert failed..");
            return false;
        }

        // new primary key
        $record["atkprimkey"] = $this->primaryKey($record);


        if (!$this->_storeAttributes($storelist["post"], $record, $mode)) {
            Tools::atkdebug("_storeAttributes failed..");
            return false;
        }

        // Now we call a postAdd function, that can be used to do some processing after the record
        // has been saved.
        if ($exectrigger && !$this->executeTrigger("postAdd", $record, $mode)) {
            return false;
        }

        return true;
    }

    /**
     * Executes a trigger on a add,update or delete action
     *
     * To prevent triggers from executing twice, the method stores an
     * indication in the record when a trigger is executed.
     * ('__executed<triggername>')
     *
     * @param string $trigger function, such as 'postUpdate'
     * @param array $record record on which action is performed
     * @param string $mode mode like add or update
     * @return bool true on case of success or when the trigger isn't returning anything (assumes success)
     */
    function executeTrigger($trigger, &$record, $mode = null)
    {
        if (!isset($record["__executed" . $trigger])) {
            $record["__executed" . $trigger] = true;

            $return = $this->$trigger($record, $mode);

            if ($return === null) {
                Tools::atkdebug("Undefined return: " . $this->atkNodeType() . ".$trigger doesn't return anything, it should return a boolean!",
                    DEBUG_WARNING);
                $return = true;
            }

            if (!$return) {
                Tools::atkdebug($this->atkNodeType() . ".$trigger failed!");
                return false;
            }

            for ($i = 0, $_i = count($this->m_triggerListeners); $i < $_i; $i++) {
                $listener = $this->m_triggerListeners[$i];
                $return = $listener->notify($trigger, $record, $mode);

                if ($return === null) {
                    Tools::atkdebug("Undefined return: " . $this->atkNodeType() . ", " . get_class($listener) . ".notify('$trigger', ...) doesn't return anything, it should return a boolean!",
                        DEBUG_WARNING);
                    $return = true;
                }

                if (!$return) {
                    Tools::atkdebug($this->atkNodeType() . ", " . get_class($listener) . ".notify('$trigger', ...) failed!");
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Delete record(s) from the database.
     *
     * After deletion, the postDel() trigger in the node method is called, and
     * on any attribute that has the AF_CASCADE_DELETE flag set, the delete()
     * method is invoked.
     *
     * NOTE: Does not commit your transaction! If you are using a database that uses
     * transactions you will need to call 'Tools::atkGetDb()->commit()' manually.
     *
     * @todo There's a discrepancy between updateDb, addDb and deleteDb:
     *       There should be a deleteDb which accepts a record, instead
     *       of a selector.
     * @param String $selector SQL expression used as where-clause that
     *                         indicates which records to delete.
     * @param bool $exectrigger wether to execute the pre/post triggers
     * @param bool $failwhenempty determine whether to throw an error if there is nothing to delete
     * @returns boolean True if successful, false if not.
     */
    function deleteDb($selector, $exectrigger = true, $failwhenempty = false)
    {
        $recordset = $this->selectDb($selector, "", "", "", "", "delete");

        // nothing to delete, throw an error (determined by $failwhenempty)!
        if (count($recordset) == 0) {
            Tools::atkwarning($this->atknodetype() . "->deleteDb($selector): 0 records found, not deleting anything.");
            return !$failwhenempty;
        }

        if ($exectrigger) {
            for ($i = 0, $_i = count($recordset); $i < $_i; $i++) {
                $return = $this->executeTrigger("preDelete", $recordset[$i]);
                if (!$return) {
                    return false;
                }
            }
        }

        if (count($this->m_cascadingAttribs) > 0) {
            for ($i = 0, $_i = count($recordset); $i < $_i; $i++) {
                for ($j = 0, $_j = count($this->m_cascadingAttribs); $j < $_j; $j++) {
                    $p_attrib = $this->m_attribList[$this->m_cascadingAttribs[$j]];
                    if (isset($recordset[$i][$this->m_cascadingAttribs[$j]]) && !$p_attrib->isEmpty($recordset[$i])) {
                        if (!$p_attrib->delete($recordset[$i])) {
                            // error
                            return false;
                        }
                    }
                }
            }
        }

        $query = $this->getDb()->createQuery();
        $query->addTable($this->m_table);
        $query->addCondition($selector);
        if ($query->executeDelete()) {
            if ($exectrigger) {
                for ($i = 0, $_i = count($recordset); $i < $_i; $i++) {
                    $return = ($this->executeTrigger("postDel", $recordset[$i]) && $this->executeTrigger("postDelete",
                            $recordset[$i]));
                    if (!$return) {
                        return false;
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Function that is called by the framework, right after a new record has
     * been saved to the database.
     *
     * This function does essentially nothing, but it can be overriden in
     * derived classes if you want to do something special after you saved a
     * record.
     *
     * @param array $record The record that has just been saved.
     * @param String $mode The 'mode' indicates whether the added record was a
     *                     completely new record ("add") or a copy ("copy").
     * @return boolean True if succesful, false if not.
     */
    function postAdd($record, $mode = "add")
    {
        // Do nothing
        return true;
    }

    /**
     * Function that is called by the framework, just before a new record will
     * be saved to the database.
     *
     * This function does essentially nothing, but it can be overriden in
     * derived classes if you want to modify the record just before it will
     * be saved.
     *
     * @param array $record The record that will be saved to the database.
     */
    function preAdd(&$record)
    {
        // Do nothing
        return true;
    }

    /**
     * Function that is called by the framework, right after an existing
     * record has been updated in the database.
     *
     * This function does essentially nothing, but it can be overriden in
     * derived classes if you want to do something special after the record is
     * updated.
     *
     * If the NF_TRACK_CHANGES flag is present for the node, both the new
     * and the original record are passed to this method. The original
     * record is stored in the new record, in $record["atkorgrec"].
     *
     * @param array $record The record that has just been updated in the
     *                      database.
     * @return boolean True if succesful, false if not.
     */
    function postUpdate($record)
    {
        // Do nothing
        return true;
    }

    /**
     * Function that is called by the framework, just before an existing
     * record will be saved to the database.
     *
     * This function does essentially nothing, but it can be overriden in
     * derived classes if you want to modify the record just before it will
     * be saved.
     *
     * @param array $record The record that will be updated in the database.
     * @return bool Wether or not we succeeded in what we wanted to do.
     */
    function preUpdate(&$record)
    {
        // Do nothing
        return true;
    }

    /**
     * Function that is called by the framework, right before a record will be
     * deleted. Should this method return false the deleting will halt.
     *
     * This function does essentially nothing, but it can be overriden in
     * derived classes if you want to do something special after a record is
     * deleted.
     *
     * If this function returns false the delete action will not continue.
     *
     * @param array $record The record that will be deleted.
     */
    function preDelete($record)
    {
        return true;
    }

    /**
     * Deprecated function that is called by the framework,
     * right after a record has been deleted.
     * Please use postDelete() instead.
     * @param array $record The record that has just been deleted.
     * @return bool Wether or not we succeeded in what we wanted to do.
     */
    function postDel($record)
    {
        // Do nothing
        return true;
    }

    /**
     * Function that is called by the framework, right after a record has been
     * deleted.
     *
     * This function does essentially nothing, but it can be overriden in
     * derived classes if you want to do something special after a record is
     * deleted.
     *
     * @param array $record The record that has just been deleted.
     * @return bool Wether or not we succeeded in what we wanted to do.
     */
    function postDelete($record)
    {
        // Do nothing
        return true;
    }

    /**
     * Function that is called by the framework, right before a copied record
     * is stored to the database.
     *
     * This function does nothing, but it can be overriden in derived classes
     * if you want to do some processing on a record before it is
     * being copied.
     * Typical usage would be: Suppose you have a field named 'title' in a
     * record. In the preCopy method, you could change the title field of the
     * record to 'Copy of ..', so the user can distinguish between the
     * original and the copy.
     *
     * @param array $record A reference to the copied record. You can change the
     *                 contents of the record, since it is passed by
     *                 reference.
     */
    function preCopy(&$record)
    {

    }

    /**
     * Function that is called for each record in a recordlist, to determine
     * what actions may be performed on the record.
     *
     * This function does nothing, but it can be overriden in derived classes,
     * to make custom actions for certain records.
     * The array with actions (edit, delete, etc.) is passed to the function
     * and can be modified.
     * To create a new action, just do $actions["new_action"]=$url;
     * in the derived function.
     * To disable existing actions, for example the edit action, for a record,
     * use: unset($actions["edit"]);
     *
     * @param array $record The record for which the actions need to be
     *                      determined.
     * @param array &$actions Reference to an array with the already defined
     *                        actions. This is an associative array with the action
     *                        identifier as key, and an url as value. Actions can be
     *                        removed from it, or added to the array.
     * @param array &$mraactions List of multirecordactions that are supported for
     *                           the passed record.
     */
    function recordActions($record, &$actions, &$mraactions)
    {
        // Do nothing.
    }

    /**
     * Registers a function/method that is called for each record in a recordlist,
     * to determine what actions may be performed on the record.
     *
     * The callback receives the record, a reference to the record actions and
     * a reference to the MRA actions as arguments.
     *
     */
    public function registerRecordActionsCallback($callback)
    {
        if (is_callable($callback, false, $callableName)) {
            if (is_array($callback)) {
                if (!method_exists($callback[0], $callback[1])) {
                    Tools::atkerror("The registered record actions callback method '$callableName' doesn't exist");
                    return;
                }
            }
            $this->m_recordActionsCallbacks[] = $callback;
        } else {
            Tools::atkerror("The registered record actions callback '$callableName' is not callable");
            return;
        }
    }

    /**
     * Function that is called for each record in a recordlist, to determine
     * what actions may be performed on the record.
     *
     * This function is a framework method and should not be called directly.
     * It should not be overridden either.
     *
     * To change the record actions, either override Node::recordActions() in you node,
     * or call Node::registerRecordActionsCallback to register a callback.
     *
     * @param array $record The record for which the actions need to be
     *                      determined.
     * @param array &$actions Reference to an array with the already defined
     *                        actions. This is an associative array with the action
     *                        identifier as key, and an url as value. Actions can be
     *                        removed from it, or added to the array.
     * @param array &$mraactions List of multirecordactions that are supported for
     *                           the passed record.
     * @return void;
     */
    public function collectRecordActions($record, &$actions, &$mraactions)
    {
        $this->recordActions($record, $actions, $mraactions);

        foreach ($this->m_recordActionsCallbacks as $callback) {
            call_user_func_array($callback, array($record, &$actions, &$mraactions));
        }
    }

    /**
     * Retrieve the security key of an action.
     *
     * Returns the privilege required to perform a certain action.
     * Usually, the privilege and the action are equal, but in m_securityMap,
     * aliasses may be defined.
     * @param String $action The action for which you want to determine the
     *                       privilege.
     * @return String The security privilege required to perform the action.
     */
    function securityKey($action)
    {
        if (!isset($this->m_securityMap[$action])) {
            return $action;
        }
        return $this->m_securityMap[$action];
    }

    /**
     * Returns the type of this node.  (This is *not* the full ATK node type;
     * see atkNodeType() for the full node type.)
     *
     * @return string type
     */
    public function getType()
    {
        return $this->m_type;
    }

    /**
     * Returns the module for this node.
     *
     * @return string node
     */
    public function getModule()
    {
        return $this->m_module;
    }

    /**
     * Returns the current action for this node.
     *
     * @return string action
     */
    public function getAction()
    {
        return $this->m_action;
    }

    /**
     * Get the full atknodetype of this node (module.nodetype notation).  This is sometimes
     * referred to as the node name (or nodename) or node string.
     *
     * @return String The atknodetype of the node.
     */
    function atkNodeType()
    {
        return (empty($this->m_module) ? "" : $this->m_module . ".") . $this->m_type;
    }

    /**
     * This function determines if the user has the privilege to perform a certain
     * action on the node.
     *
     * @param String $action The action to be checked.
     * @param array $record The record on which the action is to be performed.
     *                      The standard implementation ignores this
     *                      parameter, but derived classes may override this
     *                      method to implement their own record based
     *                      security policy. Keep in mind that a record is not
     *                      passed in every occasion. The method is called
     *                      several times without a record, to just see if
     *                      the user has the privilege for the action
     *                      regardless of the record being processed.
     *
     * @return boolean True if the action may be performed, false if not.
     */
    function allowed($action, $record = "")
    {
        $secMgr = SecurityManager::getInstance();

        $alias = $this->atkNodeType();
        $this->resolveNodeTypeAndAction($alias, $action);

        return ($this->hasFlag(NF_NO_SECURITY) || in_array($action,
                $this->m_unsecuredActions) || $secMgr->allowed($alias,
                $action) || (isset($this->m_securityImplied[$action]) && $secMgr->allowed($alias,
                    $this->m_securityImplied[$action])));
    }

    /**
     * Resolves a possible node / action alias for the given node / action.
     * The given node alias and action are updated depending on
     * the found mapping.
     *
     * @param string $alias node type
     * @param string $action action name
     */
    function resolveNodeTypeAndAction(&$alias, &$action)
    {
        if (!empty($this->m_securityAlias)) {
            $alias = $this->m_securityAlias;
        }

        // Resolve action
        $action = $this->securityKey($action);

        // If action contains a dot, it's a complete nodename.action or modulename.nodename.action alias.
        // Else, it's only an action alias, and we use the default node.

        if (strpos($action, ".") !== false) {
            $complete = explode(".", $action);
            if (count($complete) == 3) {
                $alias = $complete[0] . "." . $complete[1];
                $action = $complete[2];
            } else {
                $alias = $this->m_module . "." . $complete[0];
                $action = $complete[1];
            }
        }
    }

    /**
     * Set the security alias of a node.
     *
     * By default a node has it's own set of privileges. With this method,
     * the privileges of another node can be used. This is useful when you
     * have a master/detail relationship, and people may manipulate details
     * when they have privileges on the master node.
     * Note: When setting an alias for the node, the node no longer has to
     * have a registerNode call in the getNodes method in module.inc.
     *
     * @param String $alias The node (module.nodename) to set as a security
     *                      alias for this node.
     */
    function setSecurityAlias($alias)
    {
        $this->m_securityAlias = $alias;
    }

    /**
     * Returns the node's security alias (if set).
     *
     * @return string security alias
     */
    function getSecurityAlias()
    {
        return $this->m_securityAlias;
    }

    /**
     * Disable privilege checking for an action.
     *
     * This method disables privilege checks for the specified action, for the
     * duration of the current http request.
     * @param String $action The name of the action for which security is
     *                       disabled.
     */
    function addAllowedAction($action)
    {
        if (is_array($action)) {
            $this->m_unsecuredActions = Tools::atk_array_merge($this->m_unsecuredActions, $action);
        } else {
            $this->m_unsecuredActions[] = $action;
        }
    }



    /**
     * Retrieve help link for the current node.
     * @return String Complete html link, linking to the help popup.
     */
    function getHelp()
    {
        $res = array();
        $res["helpurl"] = $this->helpUrl();
        if ($res["helpurl"] != "") {
            $page = $this->getPage();
            $page->register_script(Config::getGlobal("assets_url") . "javascript/newwindow.js");
            $res["helplabel"] = Tools::atktext("help");
            $res["helplink"] = '<a href="' . $res["helpurl"] . '">' . $res["helplabel"] . '</a>';
        }
        return $res;
    }

    /**
     * Get img tag for lock icon.
     * @param boolean $lockstatus True if the record is locked, false if not.
     * @return String HTML image tag with the correct lock icon.
     */
    function getLockStatusIcon($lockstatus)
    {
        if ($lockstatus) {
            return Theme::getInstance()->getIcon('lock_' . $this->getLockMode(), 'lock', $this->m_module, '', null,
                array('name' => '_lock_'));
        }
        return '';
    }

    /**
     * Get the help url for this node.
     *
     * Retrieves the url of the help popup, if there is help available for
     * this node.
     * @return String The help url, or an empty string if help is not
     *                available.
     */
    function helpUrl()
    {
        $language = Config::getGlobal("language");
        $node = $this->m_type;

        $file = Module::moduleDir($this->m_module) . "help/" . $language . "/help." . $node . ".php";
        $helpmodule = "";
        if (file_exists($file)) {
            $helpmodule = $this->m_module;
        } else {
            // bwc
            $file = "help/" . $language . "/help." . $node . ".php";
            if (!file_exists($file)) {
                // no help available..
                return "";
            }
        }

        $name = Tools::atktext("help");
        return Tools::atkPopup('atk/popups/help.php', 'node=' . $node . ($helpmodule != ""
                ? "&module=" . $helpmodule : ""), $name, 650, 650, 'yes', 'no');
    }

    /**
     * Invoke the handler for an action.
     *
     * If there is a known registered external handler method for the
     * specified action, this method will call it. If there is no custom
     * external handler, the atkActionHandler object is determined and the
     * actionis invoked on the actionhandler.
     * @param String $action the node action
     */
    function callHandler($action)
    {
        Tools::atkdebug("Node::callHandler(); action: " . $action);
        $handler = Module::atkGetNodeHandler($this->m_type, $action);

        // handler function
        if ($handler != null && is_string($handler) && function_exists($handler)) {
            Tools::atkdebug("Node::callHandler: Calling external handler function for '" . $action . "'");
            $handler($this, $action);
        } // handler object
        elseif ($handler != null && $handler instanceof ActionHandler) {
            Tools::atkdebug("Node::callHandler:Using override/existing atkActionHandler " . get_class($handler) . " class for '" . $action . "'");
            $handler->handle($this, $action, $this->m_postvars);
        } // no (valid) handler
        else {
            Tools::atkdebug("Calling default handler function for '" . $action . "'");
            $this->m_handler = $this->getHandler($action);
            $this->m_handler->handle($this, $action, $this->m_postvars);
        }
    }

    /**
     * Get the atkActionHandler object for a certain action.
     *
     * The default implementation returns a default handler for the action,
     * but derived classes may override this to return a custom handler.
     * @param String $action The action for which the handler is retrieved.
     * @return ActionHandler The action handler.
     */
    function &getHandler($action)
    {
        Tools::atkdebug("Node::getHandler(); action: " . $action);

        // for backwards compatibility we first check if a handler exists without using the module name
        $handler = Module::atkGetNodeHandler($this->m_type, $action);

        // then check if a handler exists registered including the module name
        if ($handler == null) {
            $handler = Module::atkGetNodeHandler($this->atkNodeType(), $action);
        }

        // The node handler might return a class, then we need to instantiate the handler
        if (is_string($handler) && class_exists($handler)) {
            $handler = new $handler();
        }

        // The node handler might return a function as nodehandler. We cannot
        // return a function so we ignore this option.
        //       this would probably only work fine when using PHP5, but's better then nothing?
        //       or why support functions at all?!
        // handler object
        if ($handler != null && is_subclass_of($handler, "ActionHandler")) {
            Tools::atkdebug("Node::getHandler: Using existing atkActionHandler " . get_class($handler) . " class for '" . $action . "'");
            $handler->setNode($this);
            $handler->setAction($action);
        } else {
            $handler = ActionHandler::getDefaultHandler($action);
            $handler->setNode($this);
            $handler->setPostvars($this->m_postvars);
            $handler->setAction($action);

            //If we use a default handler we need to register it to this node
            //because we might call it a second time.
            Tools::atkdebug("Node::getHandler: Register default atkActionHandler for " . $this->m_type . " action: '" . $action . "'");
            Module::atkRegisterNodeHandler($this->m_type, $action, $handler);
        }

        return $handler;
    }

    /**
     * Sets the search action.
     *
     * The search action is the action that will be performed
     * if only a single record is found after doing a certain search query.
     *
     * You can specify more then 1 action. If the user isn't allowed to
     * execute the 1st action, the 2nd action will be used, etc. If you
     * want to pass multiple actions, just pass multiple params (function
     * has a variable number of arguments).
     * @todo Using func_get_args is non-standard. It's cleaner to accept an
     *       array.
     * @param String $action The name of the action.
     */
    function setSearchAction()
    {
        $this->m_search_action = func_get_args();
    }

    /**
     * This function resorts the attribIndexList and attribList.
     *
     * This is necessary if you add attributes *after* init() is already
     * called, and you set an order for those attributes.
     */
    function attribSort()
    {
        usort($this->m_attribIndexList, array("self", "attrib_cmp"));

        // after sorting we need to update the attribute indices
        $attrs = array();
        foreach ($this->m_attribIndexList as $index => $info) {
            $attr = $this->getAttribute($info['name']);
            $attr->m_index = $index;
            $attrs[$info['name']] = $attr;
        }

        $this->m_attribList = $attrs;
    }

    /**
     * Search all records for the occurance of a certain expression.
     *
     * This function searches in all fields that are not AF_HIDE_SEARCH, for
     * a certain expression (substring match). The search performed is an
     * 'or' search. If any of the fields contains the expression, the record
     * is added to the resultset.\
     *
     * Currently, searchDb only searches those attributes that are of type
     * string or text.
     *
     * @param String $expression The keyword to search for.
     * @param string $searchmethod
     * @return array Set of records matching the keyword.
     */
    function searchDb($expression, $searchmethod = "OR")
    {
        // Set default searchmethod to OR (put it in m_postvars, because selectDb
        // will use m_postvars to built it's search conditions).
        $this->m_postvars['atksearchmethod'] = $searchmethod;

        // To perform the search, we fill atksearch, so selectDb automatically
        // searches. Because an atksearch variable may have already been set,
        // we save it to restore it after the query.
        $orgsearch = Tools::atkArrayNvl($this->m_postvars, "atksearch");

        // Built whereclause.
        foreach (array_keys($this->m_attribList) as $attribname) {
            $p_attrib = $this->m_attribList[$attribname];
            // Only search in fields that aren't explicitly hidden from search
            if (!$p_attrib->hasFlag(AF_HIDE_SEARCH) && (in_array($p_attrib->dbFieldType(),
                        array("string", "text")) || $p_attrib->hasFlag(AF_SEARCHABLE))
            ) {
                $this->m_postvars['atksearch'][$attribname] = $expression;
            }
        }

        // We load records in admin mode, se we are certain that all fields are added.
        $recs = $this->selectDb("", "", "", $this->m_listExcludes, "", "admin");

        // Restore original atksearch
        $this->m_postvars['atksearch'] = $orgsearch;

        return $recs;
    }

    /**
     * Determine the url for the feedbackpage.
     *
     * Output is dependent on the feedback configuration. If feedback is not
     * enabled for the action, this method returns an empty string, so the
     * result of this method can be passed directly to the redirect() method
     * after completing the action.
     *
     * The $record parameter is ignored by the default implementation, but
     * derived classes may override this method to perform record-specific
     * feedback.
     * @param String $action The action that was performed
     * @param int $status The status of the action.
     * @param array $record The record on which the action was performed.
     * @param String $message An optional message to pass to the feedbackpage,
     *                        for example to explain the reason why an action
     *                        failed.
     * @param int $levelskip Number of levels to skip
     * @return String The feedback url.
     */
    function feedbackUrl($action, $status, $record = "", $message = "", $levelskip = null)
    {
        $controller = Controller::getInstance();
        $controller->setNode($this);
        return $controller->feedbackUrl($action, $status, $record, $message, $levelskip);
    }

    /**
     * Validates if a filter is valid for this node.
     *
     * A filter is considered valid if it doesn't contain any fields that are
     * not part of the node.
     *
     * Why isn't this used more often???
     *
     * @param String $filter The filter expression to validate
     * @returns String Returns $filter if the filter is valid or a empty
     *                 string if not.
     */
    public function validateFilter($filter)
    {
        // If the filter is blank
        // Or we can't find the target field
        if ($filter === '') {
            return $filter;
        }

        $targetField = $this->getFirstTargetFieldFromFilterSql($filter);
        if (!$targetField) {
            Tools::atkwarning($this->atkNodeType() . '->' . __FUNCTION__ . "($filter): Disallowed because it has no target field");
            // Don't allow the filter
            return '';
        }

        // Separate the table name from the column name
        $targetDetails = explode('.', $targetField);

        $targetTable = $this->m_table;
        $targetColumn = array_pop($targetDetails);

        // If no table is specified then it is implied that it is the current table
        if (count($targetDetails) == 1) {
            $targetTable = array_pop($targetDetails);
        }

        // If the table isn't $this one
        if (strtolower(trim($targetTable)) !== strtolower($this->m_table) &&
            !($this->getAttribute($targetTable) instanceof ManyToOneRelation)
        ) {
            Tools::atkwarning($this->atkNodeType() . '->' . __FUNCTION__ . "($filter): Disallowed because " . strtolower(trim($targetTable)) . " !== " . strtolower($this->m_table) . ' and not a valid many-to-one relation.');
            return '';
        }

        // Or the column doesn't belong to $this
        if (!($this->getAttribute($targetTable) instanceof ManyToOneRelation) &&
            !in_array($targetColumn, array_keys($this->m_attribList))
        ) {
            Tools::atkwarning($this->atkNodeType() . '->' . __FUNCTION__ . "($filter): Disallowed because target column $targetColumn isn't in node");
            return "";
        }
        return $filter;
    }

    /**
     * Get the targeted field and table from a snippet of filter string sql
     *
     * @param string $sql is the filter string sql
     * @return string the target table and field or an empty string
     */
    function getFirstTargetFieldFromFilterSql($sql)
    {
        // All standard SQL operators
        $sqloperators = array(
            '=',
            '<>',
            '>',
            '<',
            '>=',
            '<=',
            'BETWEEN',
            'LIKE',
            'IN',
            'IS',
            'NOT IN',
            '&'
        );

        $sqlOperatorsString = implode('|', array_map('preg_quote', $sqloperators));
        $matches = array();
        preg_match("/^(\w.+?)\s*({$sqlOperatorsString})/", str_replace('`', '', trim($sql)), $matches);

        if (count($matches) != 3) {
            return '';
        }

        return $matches[1];
    }

    /**
     * Add a stylesheet to the page.
     *
     * The theme engine is used to determine the path, and load the correct
     * stylesheet.
     * @param String $style The filename of the stylesheet (without path).
     */
    function addStyle($style)
    {
        $theme = Theme::getInstance();
        $page = $this->getPage();
        $page->register_style($theme->stylePath($style));
    }

    /**
     * Sets numbering of the attributes to begin with the number that was passed to it,
     * or defaults to 1.
     * @param mixed $number the number that the first attribute begins with
     */
    function setNumbering($number = 1)
    {
        $this->m_numbering = $number;
    }

    /**
     * Gets the numbering of the attributes
     * @return mixed the number whith which the numbering starts
     */
    function getNumbering()
    {
        return $this->m_numbering;
    }

    /**
     * Set the security of one or more actions action the same as other actions.
     * If $mapped is empty $action has to be an array. The key would be used as action and would be mapped to the value.
     * If $mapped is not empty $action kan be a string containing one action of an array with one or more action. In both
     * cases al actions would be mapped to $mappped
     * @param Mixed $action The action that has to be mapped
     * @param String $mapped The action on witch $action has to be mapped
     */
    function addSecurityMap($action, $mapped = "")
    {
        if ($mapped != "") {
            if (!is_array($action)) {
                $this->m_securityMap[$action] = $mapped;
                $this->changeMapping($action, $mapped);
            } else {
                foreach ($action as $value) {
                    $this->m_securityMap[$value] = $mapped;
                    $this->changeMapping($value, $mapped);
                }
            }
        } else {
            if (is_array($action)) {
                foreach ($action as $key => $value) {
                    $this->m_securityMap[$key] = $value;
                    $this->changeMapping($key, $value);
                }
            }
        }
    }

    /**
     * change the securitymap that already exist. Where actions are mapped on $oldmapped change it by $newmapped
     * @param string $oldmapped the old value
     * @param string $newmapped the new value with replace the old one
     */
    function changeMapping($oldmapped, $newmapped)
    {
        foreach ($this->m_securityMap as $key => $value) {
            if ($value == $oldmapped) {
                $this->m_securityMap[$key] = $newmapped;
            }
        }
    }

    /**
     * Add an atkActionListener to the node.
     *
     * @param ActionListener $listener
     */
    function addListener(&$listener)
    {
        $listener->setNode($this);

        if (is_a($listener, 'atkActionListener')) {
            $this->m_actionListeners[] = &$listener;
        } else {
            if (is_a($listener, 'atkTriggerListener')) {
                $this->m_triggerListeners[] = &$listener;
            } else {
                Tools::atkdebug('Node::addListener: Unknown listener base class ' . get_class($listener));
            }
        }
    }

    /**
     * Notify all listeners of the occurance of a certain action.
     *
     * @param String $action The action that occurred
     * @param array $record The record on which the action was performed
     */
    function notify($action, $record)
    {
        for ($i = 0, $_i = count($this->m_actionListeners); $i < $_i; $i++) {
            $this->m_actionListeners[$i]->notify($action, $record);
        }
    }

    /**
     * Notify all listeners in advance of the occurance of a certain action.
     *
     * @param String $action The action that will occur
     * @param array $record The record on which the action will be performed
     */
    function preNotify($action, &$record)
    {
        for ($i = 0, $_i = count($this->m_actionListeners); $i < $_i; $i++) {
            $this->m_actionListeners[$i]->preNotify($action, $record);
        }
    }

    /**
     * Get the column configuration object
     *
     * @param string $id optional column config id
     * @param boolean $forceNew force new instance?
     *
     * @return ColumnConfig
     */
    function &getColumnConfig($id = null, $forceNew = false)
    {
        $columnConfig = ColumnConfig::getConfig($this, $id, $forceNew);
        return $columnConfig;
    }

    /**
     * Translate using this node's module and type.
     *
     * @param mixed $string string or array of strings containing the name(s) of the string to return
     *                                when an array of strings is passed, the second will be the fallback if
     *                                the first one isn't found, and so forth
     * @param String $module module in which the language file should be looked for,
     *                                defaults to core module with fallback to ATK
     * @param String $lng ISO 639-1 language code, defaults to config variable
     * @param String $firstfallback the first module to check as part of the fallback
     * @param boolean $nodefaulttext if true, then it doesn't return a default text
     *                                when it can't find a translation
     * @return String the string from the languagefile
     */
    function text($string, $module = null, $lng = "", $firstfallback = "", $nodefaulttext = false)
    {
        if ($module === null) {
            $module = $this->m_module;
        }
        return Tools::atktext($string, $module, $this->m_type, $lng, $firstfallback, $nodefaulttext);
    }

    /**
     * String representation for this node (PHP5 only).
     *
     * @return string ATK node type
     */
    function __toString()
    {
        return $this->atkNodeType();
    }

    /**
     * Set the edit fieldprefix to use in atk
     *
     * @param string $prefix
     */
    function setEditFieldPrefix($prefix)
    {
        $this->m_edit_fieldprefix = $prefix;
    }

    /**
     * Get the edit fieldprefix to use
     *
     * @param boolean $atk_layout do we want the prefix in atkstyle (with _AE_) or not
     * @return string with edit fieldprefix
     */
    function getEditFieldPrefix($atk_layout = true)
    {
        if ($this->m_edit_fieldprefix == '') {
            return '';
        } else {
            return $this->m_edit_fieldprefix . ($atk_layout ? '_AE_' : '');
        }
    }

    /**
     * Escape SQL string, uses the node's database to do the escaping.
     *
     * @param string $string string to escape
     *
     * @return string escaped string
     */
    public function escapeSQL($string)
    {
        return $this->getDb()->escapeSQL($string);
    }

    /**
     * Row CSS class.
     *
     * Used to determine the CSS class(s) for rows in the datagrid list.
     *
     * @param array $record record
     * @param int $nr row number
     *
     * @return string CSS class(es)
     */
    public function rowClass($record, $nr)
    {
        return $nr % 2 == 0 ? 'row1' : 'row2';
    }

    /**
     * Catch missing methods.
     *
     * @param string $method method name
     * @param array $params method parameters
     */
    public function __call($method, $params)
    {
        // Catch use of deprecated selectDb and countDb methods. We implement
        // this here instead of keeping wrapper methods because if someone has
        // overridden these methods the atkCompatSelector will be used instead of
        // the normal Selector which will call the overridden selectDb and/or
        // countDb methods which might call parent::selectDb(...) which would
        // call the select() method which would again instantiate an
        // atkCompatSelector etc. This way we can make sure the atkCompatSelector is
        // only instantiated on the first call after which we use a normal
        // selector if a call to parent::selectDb(...) is made.
        if (strtolower($method) == 'selectdb') {
            Tools::atkwarning("Use of deprecated selectDb method on node " . $this->atkNodeType());

            $condition = array_key_exists(0, $params) ? $params[0] : '';
            $order = array_key_exists(1, $params) ? $params[1] : '';
            $limit = array_key_exists(2, $params) ? $params[2] : '';
            $excludes = array_key_exists(3, $params) ? $params[3] : '';
            $includes = array_key_exists(4, $params) ? $params[4] : '';
            $mode = array_key_exists(5, $params) ? $params[5] : '';
            $distinct = array_key_exists(5, $params) ? $params[5] : false;
            $ignoreDefaultFilters = array_key_exists(6, $params) ? $params[6] : false;

            $selector = new Selector($this);
            $this->_initSelector($selector);
            $selector->where($condition);
            if ($order === false || $order != '') {
                $selector->orderBy($order);
            }
            if ($limit != null && !is_array($limit)) {
                $selector->limit($limit, 0);
            } else {
                if ($limit != null) {
                    $selector->limit($limit['limit'], $limit['offset']);
                }
            }
            $selector->excludes($excludes);
            $selector->includes($includes);
            $selector->mode($mode);
            $selector->distinct($distinct);
            $selector->ignoreDefaultFilters($ignoreDefaultFilters);
            return $selector->getAllRows();
        } else {
            if (strtolower($method) == 'countdb') {
                Tools::atkwarning("Use of deprecated countDb method on node " . $this->atkNodeType());

                $condition = array_key_exists(0, $params) ? $params[0] : '';
                $excludes = array_key_exists(1, $params) ? $params[1] : '';
                $includes = array_key_exists(2, $params) ? $params[2] : '';
                $mode = array_key_exists(3, $params) ? $params[3] : '';
                $distinct = array_key_exists(4, $params) ? $params[4] : false;
                $ignoreDefaultFilters = array_key_exists(5, $params) ? $params[5] : false;

                $selector = new Selector($this);
                $this->_initSelector($selector);
                $selector->where($condition);
                $selector->excludes($excludes);
                $selector->includes($includes);
                $selector->mode($mode);
                $selector->distinct($distinct);
                $selector->ignoreDefaultFilters($ignoreDefaultFilters);
                return $selector->getRowCount();
            } else {
                throw new Exception("Call to undefined method " . get_class($this) . "::{$method}()", E_USER_ERROR);
            }
        }
    }

    /**
     * Add callback function for add css class to row.
     *
     * @param mixed $callback name of a function or array with an object
     * and the name of a method or closure
     *
     * @return boolean
     */
    public function setRowClassCallback($callback)
    {
        $res = false;
        if (is_callable($callback, false, $callableName)) {
            if (is_array($callback) && !method_exists($callback[0], $callback[1])) {
                Tools::atkerror("The registered row class callback method '$callableName' doesn't exist");
            } else {
                $this->m_rowClassCallback[] = $callback;
                $res = true;
            }
        } else {
            if (is_array($callback)) {
                if (!method_exists($callback[0], $callback[1])) {
                    Tools::atkerror("The registered row class callback method '$callableName' doesn't exist");
                }
            }
            Tools::atkerror("The registered row class callback '$callableName' is not callable");
        }
        return $res;
    }

    /**
     * Return array with callback function list, which use for add css class to row
     *
     * @return array
     */
    public function getRowClassCallback()
    {
        return $this->m_rowClassCallback;
    }

    /**
     * Adds a flag to a list of attributes.
     * @param array $attrsNames The names of attributes
     * @param int $flag The flag to add to the attributes
     * @param bool $check Check the presence of the attributes
     */
    public function addAttributesFlag($attrsNames, $flag, $check = false)
    {
        foreach ($attrsNames as $name) {
            $attr = $this->getAttribute($name);
            if (!$check || $attr) {
                $attr->addFlag($flag);
            }
        }
    }

    /**
     * Removes a flag from a list of attributes.
     * @param array $attrsNames The names of attributes
     * @param int $flag The flag to remove from the attributes
     * @param bool $check Check the presence of the attributes
     */
    public function removeAttributesFlag($attrsNames, $flag, $check = false)
    {
        foreach ($attrsNames as $name) {
            $attr = $this->getAttribute($name);
            if (!$check || $attr) {
                $attr->removeFlag($flag);
            }
        }
    }
}