const chokidar = require('chokidar');

const paths = JSON.parse(process.argv[2]);

const watcher = chokidar.watch(paths, {
    ignoreInitial: true,
});

watcher
    .on('add', path => console.log(`add - ${path}`))
    .on('change', path => console.log(`change - ${path}`))
    .on('unlink', path => console.log(`unlink - ${path}`))
    .on('addDir', path => console.log(`addDir - ${path}`))
    .on('unlinkDir', path => console.log(`unlinkDir - ${path}`))
