(function ($) {
  $(document).ready(function() {

    var $context = $('.block-recurring-donation');

    $('input.donation-amount-choice, select.donation-amount-choice, input.donation-custom-amount', $context).on('change', function() {
      var $parentForm = $(this).closest('form');
      var donationType = $(this).hasClass('recurring') ? 'recurring' : 'single';
      var selectedVal;

      if ($(this).hasClass('donation-custom-amount')){
        selectedVal = $('input[name="custom_amount"]', $parentForm).val();
      }
      else if ($(this).is('select')) {
        selectedVal = $('select[name="' + donationType + '_amount"]', $parentForm).val();
      }
      else if ($(this).is('input')) {
        selectedVal = $('input[name="' + donationType + '_amount"]:checked', $parentForm).val();
      }

      if (selectedVal === 'other') {
        selectedVal = $('input[name="custom_amount"]', $parentForm).val();
      }
      switch (donationType) {
        case 'single':
        default:
          $('input[name="amount"]', $parentForm).val(selectedVal);
          break;
        case 'recurring':
          $('input[name="a3"]', $parentForm).val(selectedVal);
          break;
      }
    });

  })
})(jQuery);
