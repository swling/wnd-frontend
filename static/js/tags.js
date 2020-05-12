let _tag_input_suggestions_data = null;

/*
create a chainnable method for the script to
*/
$.fn.tagsValues = function (method /*, args*/) {
    //loop through all tags getting the attribute value
    var data=[];
    $(this).find(".data .tag .text").each(function (key,value) {
        let v=$(value).attr('_value');
        data.push(v);
    })

    return data;
};


/*
Handle click of the input area
 */
$('.tags-input').click(function () {
    $(this).find('input').focus();
});

/*
handle the click of close button on the tags
 */

$(document).on("click", ".tags-input .data .tag .close", function() {
    // whatever you do to delete this row
    $(this).parent().remove()

})

/*
Handle the click of one suggestion
*/

$(document).on("click", ".tags-input .autocomplete-items div", function() {
    let index=$(this).index()
    let data=_tag_input_suggestions_data[index];
    let data_holder = $(this).parents().eq(4).find('.data')
    _add_input_tag(data_holder,data.id,data.name)
    $('.tags-input .autocomplete-items').html('');

})

/*
detect enter on the input
 */
$(".tags-input input").on( "keydown", function(event) {
    if(event.which == 13){
        let data = $(this).val()
        if(data!="")_add_input_tag(this,data,data)
    }


});


$(".tags-input input").on( "focusout", function(event) {
    $(this).val("")
    var that = this;
    setTimeout(function(){ $(that).parents().eq(2).find('.autocomplete .autocomplete-items').html(""); }, 500);
});


function _add_input_tag(el,data,text){
    let template="<span class=\"tag\"><span class=\"text\" _value='"+data+"'>"+text+"</span><span class=\"close\">&times;</span></span>\n";
    $(el).parents().eq(2).find('.data').append(template);
    $(el).val('')
}

$(".tags-input input").on( "keyup", function(event) {
    var query=$(this).val()

    if(event.which == 8) {
        if(query==""){
            console.log("Clearing suggestions");
            $('.tags-input .autocomplete-items').html('');
            return;
        }
    }

    $('.tags-input .autocomplete-items').html('');
    runSuggestions($(this),query)

});