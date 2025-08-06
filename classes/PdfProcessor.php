<?php
/**
 * PDF Processor Class
 * Handles PDF processing operations
 */

class PdfProcessor {
    
    /**
     * Check if Ghostscript is available
     */
    public static function isGhostscriptAvailable() {
        $gsCommands = ['gswin64c.exe', 'gswin32c.exe', 'gs'];
        
        foreach ($gsCommands as $gsCmd) {
            exec("$gsCmd --version 2>&1", $output, $returnCode);
            if ($returnCode === 0) {
                return true;
            }
            $output = [];
        }
        
        return false;
    }
    
    /**
     * Generate thumbnail from PDF first page
     */
    public static function generateThumbnail($pdfPath, $outputPath) {
        $gsCommands = ['gswin64c.exe', 'gswin32c.exe', 'gs'];
        
        foreach ($gsCommands as $gsCmd) {
            $cmd = "\"$gsCmd\" -dNOPAUSE -dBATCH -sDEVICE=png16m -dFirstPage=1 -dLastPage=1 -r150 -dGraphicsAlphaBits=4 -dTextAlphaBits=4 -sOutputFile=\"$outputPath\" \"$pdfPath\" 2>&1";
            
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($outputPath)) {
                return true;
            }
        }
        
        return false;
    }
}
?>