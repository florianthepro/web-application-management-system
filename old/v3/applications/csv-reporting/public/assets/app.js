(()=>{const q=document.querySelector('#filter');if(!q)return;q.oninput=()=>document.querySelectorAll('tbody tr').forEach(r=>r.hidden=!r.textContent.toLowerCase().includes(q.value.toLowerCase()))})();
