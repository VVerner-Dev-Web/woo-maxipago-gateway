jQuery(function ($) {
  // MASKS
  $(document).on('change', function() {
    $("#maxipago_ccNo").mask('0000 0000 0000 0000', {clearIfNotMatch: true});
    $("#maxipago_expdate").mask('00/0000', {clearIfNotMatch: true});
  })

  $(document).on('change', '#maxipago_expdate', function(e) {
    const value = $(this).val();
    const dmyDate = '01/' + $(this).val();

    if (!value) {
      return;
    }

    const ymdDate  = dmyDate.split('/').reverse().join('-');

    if (!isValidDate(ymdDate)) {
      alert('Por favor, preencha uma data de vencimento válida para o cartão. A data '+ value +' é inválida.');
      $('#maxipago_expdate').val('');
    }
  })

  function isValidDate(dateString) {
    const regEx = /^\d{4}-\d{2}-\d{2}$/;

    if(!dateString.match(regEx)) {
      return false;
    }

    const d = new Date(dateString);
    const dNum = d.getTime();

    if(!dNum && dNum !== 0) {
      return false;
    }

    return d.toISOString().slice(0,10) === dateString;
  }

});
