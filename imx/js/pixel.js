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
    const query = params();
    // persist attribution parameters
    const q = query.toString();
    const keys = ['campaign_id','submission_id','influencer_id'];
    keys.forEach(k=>{
        const v = query.get(k);
        if(v){ sessionStorage.setItem('glimmio_'+k, v); }
    });
    function stored(k){ return sessionStorage.getItem('glimmio_'+k) || null; }
    function send(event, extra){
        const payload = Object.assign({
            event: event,
            url: window.location.href,
            referrer: document.referrer || '',
            session: sid(),
            campaign_id: stored('campaign_id'),
            submission_id: stored('submission_id'),
            influencer_id: stored('influencer_id')
        }, extra||{});
        fetch('/backend/pixel.php' + (q ? '?' + q : ''), {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        });
    }
    window.glimmioPixel = {
        track: send,
        conversion: val => send('purchase', {value: val})
    };
    send('page_view');
})();
