</div> <!-- End admin-content -->
</main> <!-- End admin-main -->

<script>
    // Automatic Stock Sync every 5 minutes (300000 ms)
    setInterval(function () {
        console.log('Auto-Sync: Iniciando sincronización de stock...');
        fetch('ajax_sync_stock.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('Auto-Sync OK: ' + data.updated + ' productos actualizados a las ' + data.timestamp);
                } else {
                    console.error('Auto-Sync Error: ' + data.message);
                }
            })
            .catch(error => console.error('Auto-Sync Fetch Error:', error));
    }, 300000); 
</script>
</body>

</html>