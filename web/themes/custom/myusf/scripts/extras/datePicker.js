$("#datepicker").datepicker({ 
    altField: "#event_date",
    dateFormat: 'yy-mm-dd'})
    .datepicker("setDate", "");

$("#edit-field-start-date, #edit-field-start-date--2").datepicker({ 
    altField: "#event_date",
    dateFormat: 'yy-mm-dd 00:00:00'})
    .datepicker("setDate", "");


$("#edit-field-end-date, #edit-field-end-date--2").datepicker({ 
    altField: "#event_date",
    dateFormat: 'yy-mm-dd 23:59:59'})
    .datepicker("setDate", "");

$('#edit-field-start-date, #edit-field-start-date--2').attr('autocomplete', 'off');
$('#edit-field-end-date, #edit-field-end-date--2').attr('autocomplete', 'off');