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

function pmt2recur_civicrm_validate($formName, &$fields, &$files, &$form) {



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
        if (strlen($fields['total_amount']) > 0 && $fields['total_amount'] != $dao->amount) {
          $errors['total_amount'] = 'The total amount does not match the expected amount for the selected recurring contribution.';
        }
      }
    }
  }

  return $errors;
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
        $options = pmt2recur_build_recurring_contributions_list($contact_id);
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
      $form->addElement('static', 'script', '', "
        <script type='text/javascript'>
          //<![CDATA[
          cj().ready(function () {
            var table = cj('#contribution_recur_id').closest('table')
            var label = table.find('label')

            target_el = cj('#contribution_page_id').closest('tr')
            new_el = target_el.clone()
            new_el.find('td').empty()
            new_el.find('td').not('.label').append(cj('#contribution_recur_id'))
            new_el.find('td.label').append(label)

            target_el.after(new_el)
            table.remove()
          })

          //]]>
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

function pmt2recur_build_recurring_contributions_list($contact_id) {
  static $cache = array();
  if (!isset($cache[$contact_id])) {
    // joining with contribution table for extra checks
    $params = array(
      1 => array($contact_id, 'Int'),
    );



    // Where clause originally included:
    /*
      AND cr.contribution_status_id in (2,5,6) -- 2, 5, and 6 are: 'pending', 'in progress' and 'overdue'
      AND (cr.end_date IS NULL OR cr.end_date > now())
      AND cr.cancel_date is NULL


      $query = "
      SELECT DISTINCT
      cr.id, cp.title as page_title, cr.start_date, co.id as contribution_id,
      ct.name as contribution_type_name, cr.amount, cr.frequency_unit,
      cr.processor_id
      FROM
      civicrm_contribution_recur cr
      INNER JOIN civicrm_contribution co ON co.contribution_recur_id = cr.id
      LEFT JOIN civicrm_contribution_page cp ON cp.id = co.contribution_page_id
      LEFT JOIN civicrm_contribution_type ct ON ct.id = co.contribution_type_id
      WHERE
      cr.contact_id = %1
      AND cr.start_date < now()
      AND cr.is_test = 0
      ";
     */



    $query = "
      SELECT DISTINCT
        cr.id, cp.title as page_title, cr.start_date,
        ct.name as contribution_type_name, cr.amount, cr.frequency_unit,
        cr.processor_id
      FROM
        civicrm_contribution_recur cr
        INNER JOIN civicrm_contribution co ON co.contribution_recur_id = cr.id
        LEFT JOIN civicrm_contribution_page cp ON cp.id = co.contribution_page_id
        LEFT JOIN civicrm_financial_type ct ON ct.id = co.financial_type_id
      WHERE
        cr.contact_id = %1
        AND cr.start_date < now()
        AND cr.is_test = 0
    ";



    // print "<br><br>SQL: ".$query;
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

    $options = array();
    foreach ($contributions as $id => $values) {
      $name = 'Offline Recurring Contribution';
      if ($values['page_title']) {
        $name = '"' . $values['page_title'] . '"';
      }
      elseif ($values['membership_source']) {
        $name = '"' . $values['membership_source'] . '"';
      }
      $options[$id] = $name
        . ($values['contribution_type_name'] ? " ({$values['contribution_type_name']})" : '')
        . ($values['amount'] ? ', ' . $values['amount'] . ($values['frequency_unit'] ? "/" . $values['frequency_unit'] : '') : '')
        . ($values['start_date'] ? ', started on ' . CRM_Utils_Date::customFormat($values['start_date'], '%b %d, %Y') : '')
        . ($values['processor_id'] ? " [{$values['processor_id']}]" : '');
    }
    $cache[$contact_id] = $options;
  }
  return $cache[$contact_id];
}
