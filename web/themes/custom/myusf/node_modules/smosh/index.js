'use strict';

var serviceInfo  = {
        host: 'api.resmush.it',
        path: '/ws.php'
    },
    WebService   = require('./lib/WebService'),
    ImageFile    = require('./lib/ImageFile'),
    EventEmitter = require('events').EventEmitter;

var Smosh = function (fileBuffer, extension) {
        var smushit = null;

        if (!(this instanceof Smosh)) {
            smushit = new Smosh();

            return smushit.init(fileBuffer, extension);
        }
    },
    onError = function (vinyl, msg) {
			console.log(arguments);
        this.emit('error', msg, vinyl);
    },
    onDownload = function (vinyl, file, fileInfo) {
        var fileBuffer = new Buffer(file, 'binary');

        if (vinyl) {
            vinyl.contents = fileBuffer;
            fileBuffer     = vinyl;
        }

        this.emit('end', fileBuffer, fileInfo);
    },
    onOptimize = function (vinyl, fileInfo) {
        var imageFile = new ImageFile(fileInfo);

        imageFile
            .on('data', this.emit.bind(this, 'data'))
            .on('error', onError.bind(this, vinyl))
            .on('end', onDownload.bind(this, vinyl))
            .get();
    };

Smosh.prototype = Object.create(EventEmitter.prototype);

Smosh.prototype.init = function (vinyl) {
    var webService = new WebService(serviceInfo);

    webService
        .on('end', onOptimize.bind(this, vinyl))
        .on('error', this.emit.bind(this, 'error'))
        .execute(vinyl);

    return this;
};

module.exports = Smosh;
