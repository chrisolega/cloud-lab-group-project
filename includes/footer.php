</main>
<footer class="foot">
<p>Campus Navigator &copy; <?php echo date('Y'); ?> - CSBC 252 Cloud Computing Project</p>
</footer>
<script>
function toggleSiteTheme() {
var html = document.documentElement;
var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
html.setAttribute('data-theme', next);
localStorage.setItem('theme', next);
}
</script>
</body>
</html>
