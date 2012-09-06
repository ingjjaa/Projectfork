<?php
/**
 * @package      Projectfork
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2006-2012 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;


jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


/**
 * Form Field class for selecting a task list.
 *
 */
class JFormFieldTasklist extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var    string
     */
    public $type = 'Tasklist';


    /**
     * Method to get the field input markup.
     *
     * @return    string    The field input markup.
     */
    protected function getInput()
    {
        // Initialize variables.
        $attr   = '';
        $hidden = '<input type="hidden" id="' . $this->id . '_id" name="' . $this->name . '" value="0" />';

        // Initialize some field attributes.
        $attr .= $this->element['class']                         ? ' class="'.(string) $this->element['class'].'"' : '';
        $attr .= ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"'                          : '';
        $attr .= $this->element['size']                          ? ' size="'.(int) $this->element['size'].'"'      : '';
        $attr .= $this->multiple                                 ? ' multiple="multiple"'                          : '';

        // Handle onchange event attribute.
        if ((string) $this->element['submit'] == 'true') {
            $view = JRequest::getCmd('view');
            $attr = ' onchange="';
            if ($this->element['onchange']) $attr .= (string) $this->element['onchange'] . ';';
            $attr .= "Joomla.submitbutton('" . $view . ".setTasklist');";
            $attr .= '"';
        }
        else {
            $attr .= $this->element['onchange'] ? ' onchange="'.(string) $this->element['onchange'].'"' : '';
        }

        // Get parent item field values.
        $project   = (int) $this->form->getValue('project_id');
        $milestone = (int) $this->form->getValue('milestone_id');

        if (!$project) {
            // Cant get task list without at least a project id.
            return '<span class="readonly">' . JText::_('COM_PROJECTFORK_FIELD_PROJECT_REQ') . '</span>' . $hidden;
        }

        // Get the field options.
        $options = $this->getOptions($project, $milestone);

        // Return if no options are available.
        if (count($options) == 0) {
            return '<span class="readonly">' . JText::_('COM_PROJECTFORK_FIELD_TASKLIST_EMPTY') . '</span>' . $hidden;
        }

        return JHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
    }


    /**
     * Method to get the field list options markup.
     *
     * @param     integer    $project    The currently selected project
     * @param     integer    $milestone       The currently selected milestone
     *
     * @return    array      $options    The list options markup.
     */
    protected function getOptions($project, $milestone = 0)
    {
        $options = array();
        $user  = JFactory::getUser();
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        // Get field attributes for the database query
        $state = ($this->element['state']) ? (int) $this->element['state'] : NULL;

        // Build the query
        $query->select('a.id AS value, a.title AS text')
              ->from('#__pf_task_lists AS a')
              ->where('a.project_id = ' . (int) $project);

        // Implement View Level Access.
        if (!$user->authorise('core.admin')) {
            $groups = implode(',', $user->getAuthorisedViewLevels());
            $query->where('a.access IN (' . $groups . ')');
        }

        // Filter by milestone.
        if ($milestone) $query->where('a.milestone_id = ' . (int) $milestone);

        // Filter state
        if (!is_null($state)) $query->where('a.state = ' . $db->quote($state));

        $query->order('a.title');

        $db->setQuery((string) $query);
        $items = (array) $db->loadObjectList();

        // Generate the options
        if (count($items) > 0) {
            $options[] = JHtml::_('select.option', '',
                JText::alt('COM_PROJECTFORK_OPTION_SELECT_TASKLIST',
                preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)),
                'value',
                'text'
            );
        }

        foreach($items AS $item)
        {
            // Create a new option object based on the <option /> element.
            $opt = JHtml::_('select.option', (string) $item->value,
                JText::alt(trim((string) $item->text),
                preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)),
                'value',
                'text'
            );

            // Add the option object to the result set.
            $options[] = $opt;
        }

        reset($options);

        return $options;
    }
}
