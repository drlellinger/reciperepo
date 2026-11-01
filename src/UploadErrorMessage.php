<?php
declare(strict_types=1);

namespace Recipe;

final class UploadErrorMessage
{
    public static function forCode(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'Die Datei ist größer als das vom Server erlaubte Limit. Bitte upload_max_filesize und post_max_size prüfen.',
            UPLOAD_ERR_FORM_SIZE => 'Die Datei überschreitet das vom Formular erlaubte Limit.',
            UPLOAD_ERR_PARTIAL => 'Der Upload wurde nur teilweise übertragen. Bitte erneut versuchen.',
            UPLOAD_ERR_NO_FILE => 'Es wurde keine Datei ausgewählt.',
            UPLOAD_ERR_CANT_WRITE => 'Das temporäre Verzeichnis für den Upload ist nicht beschreibbar.',
            UPLOAD_ERR_EXTENSION => 'Ein PHP-Upload-Filter hat den Upload blockiert.',
            default => 'Das Bild konnte nicht hochgeladen werden.',
        };
    }
}
