(function(){
    function params() {
        return new URLSearchParams(window.location.search);
    }
    function send(event) {
        fetch('/backend/pixel.php?' + params().toString(), {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({event:event})
        });
    }
    window.glimmioPixel = {track: send};
    send('page_view');
})();
