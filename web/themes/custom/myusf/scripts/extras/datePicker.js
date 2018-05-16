$("#datepicker").datepicker({ 
    altField: "#event_date",
    dateFormat: 'yy-mm-dd'})
    .datepicker("setDate", "{{ event_date }}");