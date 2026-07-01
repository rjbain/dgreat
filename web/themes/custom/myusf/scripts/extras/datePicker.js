(function ($) {
  function splitDateTime(value) {
    if (!value) {
      return {
        date: '',
        time: '',
      };
    }

    var parts = value.split(' ');
    return {
      date: parts[0] || '',
      time: parts[1] || '',
    };
  }

  function ensureHiddenDateTimeField($input, timeSuffix) {
    var inputName = $input.attr('name');
    var inputId = $input.attr('id');

    if (!inputName || !inputId) {
      return null;
    }

    var hiddenId = inputId + '-hidden';
    var $hidden = $('#' + hiddenId);

    if (!$hidden.length) {
      $hidden = $('<input>', {
        type: 'hidden',
        id: hiddenId,
        name: inputName,
      });
      $input.after($hidden);
    }

    $input.removeAttr('name');

    var current = splitDateTime($input.val());
    if (current.date) {
      $input.val(current.date);
      $hidden.val(current.date + ' ' + timeSuffix);
    }
    else {
      $hidden.val('');
    }

    $input.datepicker({
      dateFormat: 'yy-mm-dd',
      altField: '#' + hiddenId,
      altFormat: 'yy-mm-dd ' + timeSuffix,
    });

    $input.on('change blur', function () {
      var rawValue = $input.val().trim();
      if (!rawValue) {
        $hidden.val('');
        return;
      }

      $hidden.val(rawValue + ' ' + timeSuffix);
    });

    return $hidden;
  }

  $('#datepicker').datepicker({
    altField: '#event_date',
    dateFormat: 'yy-mm-dd',
  }).datepicker('setDate', '');

  $('#edit-field-start-date, #edit-field-start-date--2').each(function () {
    ensureHiddenDateTimeField($(this), '00:00:00');
  });

  $('#edit-field-end-date, #edit-field-end-date--2').each(function () {
    ensureHiddenDateTimeField($(this), '23:59:59');
  });

  $('#edit-field-start-date, #edit-field-start-date--2, #edit-field-end-date, #edit-field-end-date--2').attr('autocomplete', 'off');
})(jQuery);
