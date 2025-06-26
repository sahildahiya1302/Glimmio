(function(){
    function params(){
        return new URLSearchParams(window.location.search);
    }
    function sid(){
        let id = localStorage.getItem('glimmio_sid');
        if(!id){
            id = Math.random().toString(36).slice(2);
            localStorage.setItem('glimmio_sid', id);
        }
        return id;
    }
    function send(event, extra){
        const payload = Object.assign({
            event: event,
            url: window.location.href,
            referrer: document.referrer || '',
            session: sid()
        }, extra||{});
        fetch('/backend/pixel.php?' + params().toString(), {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        });
    }
    window.glimmioPixel = {track: send};
    send('page_view');
})();
