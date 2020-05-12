/*
[
    {"name": "Nairobi","id": 1},
    {"name": "Mombasa","id": 2},
]
*/
function runSuggestions(element, query) {
    /*
    using ajax to populate suggestions
     */
    let sug_area = $(element).parents().eq(2).find(".autocomplete .autocomplete-items");
    $.getJSON(wnd_jsonget_api, {
        "data": "wnd_term_search",
        "param": query
    }, function(data) {
        _tag_input_suggestions_data = data;
        $.each(data, function(key, value) {
            let template = $("<div>" + value.name + "</div>").hide()
            sug_area.append(template)
            template.show()

        })
    });
}

jQuery(document).ready(function($) {

    console.log($("#post-tags-input").tagsValues())

    /*
    detect enter on the input
     */
    $(".tags-input input").on("keydown", function(event) {
        if (event.which == 13) {
            console.log($("#post-tags-input").tagsValues())
        }
    });

    $(document).on("click", ".tags-input .data .tag .close", function() {
        // whatever you do to delete this row
        console.log($("#post-tags-input").tagsValues())

    })

});