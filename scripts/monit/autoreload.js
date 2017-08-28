const fs = require('fs');
const { exec } = require('child_process');
const chokidar = require('chokidar');

const watcher = chokidar.watch('*.php', {
    persistent: true,
    ignored: '*.xml',
});

exec('php server.php', (error, stdout, stderr) => {
    if (error) {
        throw error;
    }
    console.log(stdout);
});

watcher.on('change', (path, stats) => {
    if (stats) console.log(`File ${path} changed size to ${stats.size}`);
    fs.readFile('./server.pid', 'utf8', (err, data) => {
        if (err) throw err;
        console.log(data);
        process.kill(data, 'SIGUSR1');
    });
});
