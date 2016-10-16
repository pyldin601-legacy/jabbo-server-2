$(document).ready(function(){
    $("span.filter").click(function(){
        applyFilter($(this).text());
    });
});

function applyFilter(txt) {
    console.log(txt);
}