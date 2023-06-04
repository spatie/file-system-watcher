const chokidar = require('chokidar');

const paths = JSON.parse(process.argv[2]);

const watcher = chokidar.watch(paths, {
    ignoreInitial: true,
    awaitWriteFinish: {
        stabilityThreshold: 2000,
        pollInterval: 100
    }
});

watcher
    .on('add', path => console.log(`fileCreated - ${path}`))
    .on('change', path => console.log(`fileUpdated - ${path}`))
    .on('unlink', path => console.log(`fileDeleted - ${path}`))
    .on('addDir', path => console.log(`directoryCreated - ${path}`))
    .on('unlinkDir', path => console.log(`directoryDeleted - ${path}`))
