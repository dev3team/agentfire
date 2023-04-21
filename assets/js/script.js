const baseUrl = '/wp-json/agentfire/v1/test/markers';

var myOnly = false;
var searchQuery = '';

var tagSlugs = [];
var tagsJson = [];
var glMarkers = [];


var testJs = {
    /** choose action for marker */
    selectMarker: function (_this){
        var attribule = _this.getAttribute('id');
        var parent = _this.closest('form');
        var chosen = jQuery('.chosen-select');
        parent.querySelectorAll('[data-event]').forEach(function (el){
            el.classList.add('hidden');
            el.value = '';
        })
        parent.querySelector('[data-event="'+attribule+'"]').classList.remove('hidden');
        chosen.chosen('destroy');
        switch (_this.value){
            case 'create':
                break;
            default:
                chosen.html('');
                chosen.val('');
                chosen.append('<option></option>');
                tagsJson.forEach(function(el){
                    chosen.append('<option value="'+el.name+'">'+el.name+'</option>');
                })
                chosen.chosen();
                break;
        }
    },

    /** sending requests to REST API */
    request: function (url, data, callback, method){
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                /** processing REST API response */
                callback(this.responseText);
            }
        };
        xhttp.open(method, url, true);
        xhttp.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
        xhttp.setRequestHeader('X-WP-Nonce', wpNonce);
        xhttp.send(data);
    },

    /** Creating marker */
    createMarker: function (event, form) {
        event.preventDefault();
        var data = {};
        form.querySelectorAll('input').forEach(function (el) {
            /** get values from all inputs, excluding 'submit' */
            if (el.type !== 'submit') {
                data[el.name] = el.value;
            }
        });

        /** request for creating */
        this.request(baseUrl, JSON.stringify(data), function (){
            form.reset();
            alert('Added new mark!');
            testJs.loadMarkers();
            testJs.loadTags();
        }, "POST")
    },

    /** loading markers */
    loadMarkers: function (){
        var reqUrl = baseUrl;
        const tagCount = tagSlugs.length;

        /** check filters by tag */
        if ( tagCount > 0 ) {
            reqUrl += '?';

            /** add filters by tags */
            for (var i = 0; i < tagCount; ++i) {
                reqUrl += ('tags[]=' + tagSlugs[i] + ( (i === tagCount - 1) ? '' : '&' ) );
            }

            /** search */
            if ( searchQuery !== '' )
                reqUrl += '&search=' + searchQuery;
        } else {
            /** search */
            if ( searchQuery !== '' )
                reqUrl += '?search=' + searchQuery;
        }

        /** filter if my markers only */
        if ( myOnly )
            reqUrl += (reqUrl.indexOf('?') === -1 ? '?' : '&') + 'my_only=1';

        /** request to get markers */
        this.request(reqUrl, {}, function (responce){
            var markers = JSON.parse(responce);

            /** remove old markers */
            const oldLen = glMarkers.length;
            if ( oldLen > 0 ) {
                /** remove from map */
                for (var i = oldLen - 1; i >= 0; i--) {
                    glMarkers[i].remove();
                }

                /** remove from array */
                glMarkers = [];
            }

            /** new markers */
            const len = markers.length;
            if ( len > 0 ) {
                for (var i = 0; i < len; ++i) {
                    /** Add marker on map */
                    var newMarker = new mapboxgl.Marker({ color: markers[i].is_my ? 'blue' : 'red'})
                        .setLngLat([markers[i].lng, markers[i].lat])
                        .setPopup(
                            new mapboxgl.Popup().setHTML(
                                '<h3>' + markers[i].name + '</h3>' +
                                '<p><b>Tags:</b> ' + markers[i].tags + '</p>' +
                                '<p><b>Created:</b> ' + markers[i].date + '</p>')
                        )
                        .addTo(map);

                    /** add marker to array */
                    glMarkers.push(newMarker);
                }
            }
        }, "GET")
    },

    /** loading tags */
    loadTags: function (){
        this.request("/wp-json/agentfire/v1/test/tags", {}, function (responce){
            var tags = JSON.parse(responce);
            tagsJson = tags;
            const len = tags.length;
            if ( len > 0 ) {
                const filtersBlock = document.querySelector('.tags-wrapper');
                var selectorTag = filtersBlock.querySelectorAll('.input-group.tags');
                filtersBlock.innerHTML = '';

                /** remove checkboxes for old tags */
                if (selectorTag.length > 0)
                    selectorTag.forEach(function (el) {
                        el.remove();
                    });

                /** creating checkboxes for new loaded tags */
                for (var i = 0; i < len; ++i) {
                    var elem = document.createElement('div');
                    elem.className = 'input-group tags';

                    elem.innerHTML = '<div class="input-group-text">'
                        + '<input type="checkbox" id="tag_' + tags[i].id + '" value="' + tags[i].slug + '" onchange="testJs.checkFilter(event)">'
                        + '<label for="tag_' + tags[i].id + '">' + tags[i].name + '</label>'
                        + '</div>';

                    filtersBlock.appendChild(elem);
                }
            }
        }, "GET")
    },

    /** Changing search input value */
    searchMarkers: function (e){
        searchQuery = e.target.value;
        this.loadMarkers();
    },

    /** Change filter by tags */
    checkFilter: function (event){
        var tagSlug = event.target.value;
        if ( event.target.checked ) {
            tagSlugs.push(tagSlug);
        } else {
            tagSlugs.splice( tagSlugs.indexOf(tagSlug), 1 );
        }

        this.loadMarkers();
    },

    /** toggle handler (All markers\Own markers) */
    toggleOwn: function (e){
        myOnly = e.target.value;
        this.loadMarkers();
    },


    duplicate(_this){
        var chosen = jQuery('.chosen-select');
        _this.closest('form').querySelector('[name="tags"]').value = chosen.chosen().val().join(',');
    },

    /** Initialization of map */
    initMap: function (){
        /** Map click handler */
        map.on('click', (e) => {
            if ( !e.originalEvent.target.classList.contains('mapboxgl-canvas') ) return;
            var coordinates = e.lngLat;
            var mbPopupHtml = '';
            if (currentUserId) {
                /** form for adding new marker (if user is authorized) */
                mbPopupHtml = 'Add marker here: <br/>' + coordinates + '<br/>'
                    + '<form id="marker_form" class="marker-form" onsubmit="testJs.createMarker(event, this);">'
                        + '<input type="hidden" name="lat" value="' + coordinates.lat + '">'
                        + '<input type="hidden" name="lng" value="' + coordinates.lng + '">'
                        + '<input type="text" name="name" required placeholder="Name"><br/>'
                        + '<b>Choose an action</b>'
                        + '<div class="radio-wrapper">'
                            + '<div class="radio-label"><input type="radio" id="create-marker" onchange="testJs.selectMarker(this)" name="event" value="create"><label for="create-marker">Create Marker</label></div>'
                            + '<div class="radio-label"><input type="radio" id="select-marker" onchange="testJs.selectMarker(this)" name="event" value="select"><label for="select-marker">Select Marker</label></div>'
                        + '</div>'
                        + '<input type="text" name="tags" class="hidden" data-event="create-marker" placeholder="Tag1,Tag2">'
                        + '<select style="width: 100%" class="select-marker hidden chosen-select" onchange="testJs.duplicate(this)" multiple data-event="select-marker"></select><br/>'
                        + '<input type="submit" value="Create marker"><br/>'
                    + '</form>';
            } else {
                /** information about point (if user is not authorized) */
                mbPopupHtml = 'you clicked here: <br/>' + coordinates;
            }

            new mapboxgl.Popup()
                .setLngLat(coordinates)
                .setHTML(mbPopupHtml)
                .addTo(map);
        });
    },
    init: function (){
        this.initMap();
        this.loadMarkers();
        this.loadTags();
    }
}

/** Initialization after window loading */
window.addEventListener('load', function () {
    testJs.init();
});
