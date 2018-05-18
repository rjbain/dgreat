smosh [![Build Status](https://travis-ci.org/heldr/smosh.svg?branch=master)](https://travis-ci.org/heldr/smosh)
=====

A middleware between [resmushit](http://resmush.it/) and streams on top of [vinyl](https://www.npmjs.com/package/vinyl).

How to use?
------------

```shell
npm install smosh --save
```

```javascript
var smosh = require('smosh'),
    file  = smosh(vinylFile),
    data  = '';

file.on('data', function(chunk) {
    // file chunk stream
    data += chunk;
});

file.on('end', function(newBuffer, info) {
    // vinyl metadata if previously given
    console.log(newBuffer.toString());
    // or if data is streamed
    console.log(data);
    // percentage compressed
    console.log(info.percent)
});

file.on('error', function(err) {
    throw err;
});
```

## License

MIT © [Helder Santana](https://github.com/heldr)


Based on: [node-smushit](https://github.com/colorhook/node-smushit)
