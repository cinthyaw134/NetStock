<script>
(function(){
    const key='inventory-theme';
    const saved=localStorage.getItem(key);
    if(saved==='dark') document.body.classList.add('dark-mode');
    function updateIcon(){
        const icon=document.getElementById('theme-icon');
        if(icon) icon.textContent=document.body.classList.contains('dark-mode')?'☀':'☾';
    }
    updateIcon();
    const toggle=document.getElementById('theme-toggle');
    if(toggle) toggle.addEventListener('click',function(){
        document.body.classList.toggle('dark-mode');
        localStorage.setItem(key,document.body.classList.contains('dark-mode')?'dark':'light');
        updateIcon();
    });
})();
</script>
</main></div></body></html>
