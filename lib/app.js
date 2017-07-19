jQuery(function() {
    new JSNES({
        'swfPath': retroGameEmulator.swfPath,
        'ui': jQuery('.jsnes').text('').JSNESUI({
            "Working": retroGameEmulator.roms
        })
    });
});