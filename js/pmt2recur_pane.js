(function($, ts)   {

  /**
   * Check wither a selected recurring contribution has no completed payments;
   * if so, present an error message and, if relevant, prevent the event default,
   * e.g., prevent the form submission.
   *
   * This fires upon selection
   *
   * @param Event e
   * @returns Boolean
   */
  CRM.pmt2recurPrevalidateContributionsWithNoCompletedPayments = function(e) {
    var contribution_recur_id = $('#contribution_recur_id').val();
    if (! (CRM.vars.pmt2recur.contribution_recur_details[contribution_recur_id].has_completed_contributions * 1)) {
      CRM.alert(
        ts('The selected recurring contribution has no completed installments associated with it, so additional installments cannot be linked. If you are recording a transaction that has already been completed through your payment processor, please record it as a one-time transaction, then cancel the existing recurring contribution and create a new recurring contribution for the remaining installments due.'),
        ts('Invalid choice'),
        'error'
      );
      e.preventDefault();
      return false;
    }
    return true;
  }


  /**
   * Change handler for contribution_recur_id field.
   * @returns {undefined}
   */
  CRM.pmt2recurSetFieldsPerRecurringContribution = function () {
    // Define vars to store relevant values
    var contribution_recur_id = $('#contribution_recur_id').val();
    var original_financial_type_id = $('#financial_type_id').val();
    var original_total_amount = $('#total_amount').val();

    // Remove any existing alert. We'll add it below if it's needed.
    $('#pmt2recur-contribution_recur_id-alert').remove();
    var alert_text = '';

    if (contribution_recur_id > 0) {
      var new_financial_type_id = CRM.vars.pmt2recur.contribution_recur_details[contribution_recur_id].financial_type_id;
      var new_total_amount = CRM.vars.pmt2recur.contribution_recur_details[contribution_recur_id].amount;

      if (new_total_amount != original_total_amount || new_financial_type_id != original_financial_type_id) {
        $('#financial_type_id').val(new_financial_type_id);
        $('#total_amount').val(new_total_amount);
        alert_text = ts('Based on the selected recurring contribution, some fields have been updated to new values.<br />');
        alert_text += ' <strong>' + ts('Total Amount') + '</strong>: <em>'+ new_total_amount + '</em>;';
        alert_text += ' <strong>' + ts('Financial Type') + '</strong>: <em>'+ $('#financial_type_id option[value='+ new_financial_type_id +']').text() + '</em>';
      }
    }
    else {
        $('#financial_type_id').val('');
        $('#total_amount').val('');
        alert_text = ts('To match the selection of no recurring contribution, some fields have been emptied.<br />');
        alert_text += ' <strong>' + ts('Total Amount') + '</strong>, <strong>' + ts('Financial Type') + '</strong>';
    }
    if (alert_text.length) {
      $('#contribution_recur_id').after('<div id="pmt2recur-contribution_recur_id-alert" class="crm-error">'+ alert_text +'</div>')
    }
  }


  $().ready(function () {
    // Move "contribution_recur_id" field into form table structure.
    var table = $('#contribution_recur_id').closest('table');
    var tr = $('#contribution_recur_id').closest('tr');
    $('#contribution_page_id').closest('tr').after(tr);
    table.remove();

    // Define a change handler for the "contribution_recur_id" field.
    $('#contribution_recur_id').change(CRM.pmt2recurSetFieldsPerRecurringContribution);
    $('#contribution_recur_id').change(CRM.pmt2recurPrevalidateContributionsWithNoCompletedPayments);

    $('#contribution_recur_id').closest('form').find('input[type="submit"]:not(#_qf_Contribution_cancel)').click(CRM.pmt2recurPrevalidateContributionsWithNoCompletedPayments);


    // Prepend an explanation about contributions with no completed payments.
    $('#contribution_recur_id').before('<div class="help">'+ ts('* Items marked with an asterisk are not available for selection.') +'</div>');
  });

}(CRM.$, CRM.ts('com.pogstone.pmt2recur')));