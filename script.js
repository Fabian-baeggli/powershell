console.log('NEUES SCRIPT.JS GELADEN');

document.addEventListener('DOMContentLoaded', ()=> {
  const userCards = document.getElementById('user-cards');
  const popup    = document.getElementById('match-popup');
  const matchName= document.getElementById('match-name');
  const goChat   = document.getElementById('go-chat');
  const closePop = document.getElementById('close-popup');

  // Lade Nutzer-Karten
  fetch('get_users.php')
    .then(r=>r.json())
    .then(users=>{
      users.forEach(u=>{
        const c=document.createElement('div');
        c.className='card';
        c.innerHTML=`
          <h3>${u.username}</h3>
          <button data-id="${u.id}" data-name="${u.username}">Like</button>
        `;
        userCards.append(c);
      });
    });

  // Like-Button-Handler
  userCards.addEventListener('click', e=>{
    if (e.target.tagName==='BUTTON') {
      const id = e.target.dataset.id;
      const nm = e.target.dataset.name;
      fetch('like.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`user_id=${id}&username=${encodeURIComponent(nm)}`
      })
      .then(r=>r.json())
      .then(res=>{
        if (res.success) {
          if (res.isMatch) {
            // Zeige das Match-Popup an
            const matchUser = res.match;
            document.getElementById('match-name').textContent = matchUser.username || 'jemandem';
            // Optional: Match-Bild anzeigen
            // document.getElementById('match-image').src = matchUser.photo_path || 'default_avatar.png';
            document.getElementById('go-chat').onclick = () => {
                window.location.href = `messages.php?user_id=${matchUser.id}`;
            };
            document.getElementById('match-popup').classList.remove('hidden');
          }
          // Karte nach erfolgreichem Like/Dislike entfernen
          e.target.closest('.card-container').remove();
        } else {
          // Zeige die spezifische Fehlermeldung vom Server an
          alert(res.message || 'Ein unbekannter Fehler ist aufgetreten.');
        }
      })
      .catch(err => {
        // Zeige Netzwerk- oder JSON-Parsing-Fehler an
        console.error('Fetch Error:', err);
        alert('Ein technischer Fehler ist aufgetreten. Bitte versuche es später erneut.');
      });
    }
  });

  if (closePop) {
    closePop.onclick = ()=> popup.classList.add('hidden');
  }

  // Chat senden
  document.body.addEventListener('submit', e=>{
    if (e.target.id==='chat-form') {
      e.preventDefault();
      const fd = new FormData(e.target);
      fetch('send_message.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(()=> location.reload());
    }
  });
});
