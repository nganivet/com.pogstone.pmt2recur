<?php

require_once 'pmt2recur.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function pmt2recur_civicrm_config(&$config) {
  _pmt2recur_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function pmt2recur_civicrm_xmlMenu(&$files) {
  _pmt2recur_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function pmt2recur_civicrm_install() {
  return _pmt2recur_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function pmt2recur_civicrm_uninstall() {
  return _pmt2recur_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function pmt2recur_civicrm_enable() {
  return _pmt2recur_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function pmt2recur_civicrm_disable() {
  return _pmt2recur_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function pmt2recur_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _pmt2recur_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function pmt2recur_civicrm_managed(&$entities) {
  return _pmt2recur_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function pmt2recur_civicrm_caseTypes(&$caseTypes) {
  _pmt2recur_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function pmt2recur_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _pmt2recur_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function pmt2recur_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  /*
    if (!user_access('access civicrm_pmt2recur')) {
    return;
    }
   */
  $errors = array();
  if ($formName == 'CRM_Contribute_Form_Contribution') {


    if ($fields['contribution_recur_id']) {
      require_once 'CRM/Contribute/BAO/ContributionRecur.php';

      $dao = new CRM_Contribute_BAO_ContributionRecur();
      $dao->id = $fields['contribution_recur_id'];
      $dao->find();
      if ($dao->fetch()) {

        // With priceset contributions, the $fields['total_amount'] value is empty/blank. No way to validate the amounts match
        // in this situation.
        if (!empty($fields['total_amount']) && $fields['total_amount'] != $dao->amount) {
          $errors['total_amount'] = ts(
            'The total amount does not match the expected amount (%1) for the selected recurring contribution.',
            array(
              'domain' => 'pmt2recur',
              '%1' => "<em>{$dao->amount}</em>",
            )
          );
        }
        if (!empty($fields['financial_type_id']) && $fields['financial_type_id'] != $dao->financial_type_id) {
          $result = civicrm_api3('FinancialType', 'getvalue', array(
            'sequential' => 1,
            'return' => "name",
            'id' => $dao->financial_type_id,
          ));
          $errors['financial_type_id'] = ts(
            'The financial type does not match the expected financial type (%1) for the selected recurring contribution.',
            array(
              'domain' => 'pmt2recur',
              '%1' => "<em>{$result}</em>",
            )
          );
        }
      }
    }
  }
}

function pmt2recur_civicrm_pre($op, $objectName, $id, &$params) {
  /*
    if (!user_access('access civicrm_pmt2recur')) {
    return;
    }
   */

  if ($objectName == 'Contribution') {
    if ($op == 'edit' || $op == 'create') {
      $contribution_recur_id = CRM_Utils_Array::value('contribution_recur_id', $_POST);
      if ($contribution_recur_id) {
        $params['contribution_recur_id'] = $contribution_recur_id;
      }
    }
  }
}

/**
 * Implements hook_civicrm_buildForm().
 */
function pmt2recur_civicrm_buildForm($formName, &$form) {

  /*
    if (!user_access('access civicrm_pmt2recur')) {
    return;
    }
   */

  if ($formName == 'CRM_Contribute_Form_Contribution') {
    // FIXME: add checks for these cases:
    // - form element #is_recur value==1
    $is_base_form = !CRM_Utils_Array::value('snippet', $_GET);
    $is_target_pane = (!$is_base_form && CRM_Utils_Array::value('formType', $_GET) == 'AdditionalDetail');

    if ($is_base_form || $is_target_pane) {
      $contact_id = $form->_contactID;
      if ($contact_id) {
        // Build a list of current existing subscription_ids for the contact.
        // FIXME: This needs to be built dynamically when contact is selected on new payments.
        $options = pmt2recur_build_recurring_contributions_list($contact_id, 'option_label');
      }
      else {
        $options = array();
      }
      $has_options = FALSE;
      if (!empty($options)) {
        $has_options = TRUE;
        $empty_options = array(
          '' => ' - select - ',
        );
        $options = $empty_options + $options;
        $form->addElement('select', 'contribution_recur_id', ts('Recurring Contribution'), $options);
        CRM_Core_Resources::singleton()->addScriptFile('com.pogstone.pmt2recur', 'js/pmt2recur.js');

        // Pass details of recurring contributions to JS scope.
        $settings = array(
          'contribution_recur_details' => pmt2recur_build_recurring_contributions_list($contact_id),
        );
        CRM_Core_Resources::singleton()->addVars('pmt2recur', $settings);

        if ($form->_id) {
          require_once 'api/api.php';
          $api_params = array(
            'version' => 3,
            'id' => $form->_id,
          );
          $result = civicrm_api('contribution', 'get', $api_params);
          if (!$result['is_error'] && $result['count']) {
            $defaults = array(
              'contribution_recur_id' => $result['values'][$form->_id]['contribution_recur_id'],
            );
            $form->setDefaults($defaults);
          }
        }
      }
    }

    if ($has_options && $is_target_pane) {
      // There's probably a better way to do this; the point is merely to add
      // some javasript that will run when the 'AdditionalDetail' accordion is
      // expanded. CRM_Core_Resources::singleton()->addScriptFile() doesn't do
      // it, perhaps because it's not a full page load with headers and footers.
      $form->addElement('static', 'script', '', "
        <script type='text/javascript'>
          //<![CDATA[
        " .
        file_get_contents(CRM_Core_Resources::singleton()->getPath('com.pogstone.pmt2recur', 'js/pmt2recur_pane.js'))
        . "  //]]>
        </script>
      ");

      // Add the new element(s) to CiviCRM's beginHookFormElements area, and
      // the above JavaScript will move it to the correct place in the form.
      $tpl = CRM_Core_Smarty::singleton();
      $bhfe = $tpl->get_template_vars('beginHookFormElements');
      if (!$bhfe) {
        $bhfe = array();
      }
      $bhfe[] = 'contribution_recur_id';
      $bhfe[] = 'script';
      $form->assign('beginHookFormElements', $bhfe);
    }
  }
}

/**
 * Get desired details of available recurring contributions for the given contact.
 * 
 * @staticvar array $cache
 * @param Int $contact_id CiviCRM Contact ID for the contact.
 * @param String $column Name of the SQL column to retrieve per contribution. If
 *   not given, the return value is a three-dimensional array with contribution
 *   IDs for keys, each value being an associative array of all columns for
 *   that contribution. If given as one of the following, the return value is a
 *   two-dimensional array having contribution IDs for keys, each value being
 *   the value of the given column.
 * @return Array
 */
function pmt2recur_build_recurring_contributions_list($contact_id, $column = NULL) {
  static $cache = array();
  if (!isset($cache[$contact_id])) {
    // joining with contribution table for extra checks
    $params = array(
      1 => array($contact_id, 'Int'),
    );

    // TODO: use APIs here for more durable code.
    $query = "
      SELECT DISTINCT
        cr.id, cp.title as page_title, cr.start_date,
        ct.name as contribution_type_name, cr.amount, cr.frequency_unit,
        cr.processor_id, cr.financial_type_id
      FROM
        civicrm_contribution_recur cr
        INNER JOIN civicrm_contribution co ON co.contribution_recur_id = cr.id
        LEFT JOIN civicrm_contribution_page cp ON cp.id = co.contribution_page_id
        LEFT JOIN civicrm_financial_type ct ON ct.id = cr.financial_type_id
      WHERE
        cr.contact_id = %1
        AND cr.start_date < now()
        AND cr.is_test = 0
    ";

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $contributions = $contribution_ids = array();
    while ($dao->fetch()) {
      $contributions[$dao->id] = $dao->toArray();
    }

    if (empty($contributions)) {
      return array();
    }

    // Add membership info.
    $query = "
      SELECT m.source, m.contribution_recur_id
      FROM civicrm_membership m
      WHERE m.contribution_recur_id IN (" . implode(',', array_keys($contributions)) . ")
    ";
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $contributions[$dao->contribution_recur_id]['membership_source'] = $dao->source;
    }

    foreach ($contributions as $id => $values) {
      $name = 'Offline Recurring Contribution';
      if ($values['page_title']) {
        $name = '"' . $values['page_title'] . '"';
      }
      elseif ($values['membership_source']) {
        $name = '"' . $values['membership_source'] . '"';
      }
      $contributions[$id]['option_label'] = $name
        . ($values['contribution_type_name'] ? " ({$values['contribution_type_name']})" : '')
        . ($values['amount'] ? ', ' . $values['amount'] . ($values['frequency_unit'] ? "/" . $values['frequency_unit'] : '') : '')
        . ($values['start_date'] ? ', started on ' . CRM_Utils_Date::customFormat($values['start_date'], '%b %d, %Y') : '')
        . ($values['processor_id'] ? " [{$values['processor_id']}]" : '');
    }
    $cache[$contact_id] = $contributions;
  }

  if ($column !== NULL) {
    return array_column($cache[$contact_id], $column, 'id');
  }
  else {
    return $cache[$contact_id];
  }
}
