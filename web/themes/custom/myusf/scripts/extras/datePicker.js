$("#datepicker").datepicker({ 
    altField: "#event_date",
    dateFormat: 'yy-mm-dd'})
    .datepicker("setDate", "");

$("#edit-field-start-date").datepicker({ 
    altField: "#event_date",
    dateFormat: 'yy-mm-dd 00:00:00'})
    .datepicker("setDate", "");


$("#edit-field-end-date").datepicker({ 
    altField: "#event_date",
    dateFormat: 'yy-mm-dd 23:59:59'})
    .datepicker("setDate", "");

$('#edit-field-start-date').attr('autocomplete', 'off');
$('#edit-field-end-date').attr('autocomplete', 'off');