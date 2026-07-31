<?php
namespace Biblhertz\Manifest_Server\api;


/********************************************************************/
/*		REPRESENTATION OF BASE CONTROLLER							*/
/*																	*/
/*																	*/
/********************************************************************/


class IIIFValidator
{
    private $validationServices = [
        'iiif_validator' => 'https://iiif.io/api/presentation/validator/service/validate',
        'github_validator' => 'https://presentation-validator.iiif.io/validate',
    ];
    
    private $timeout = 30;
    private $userAgent = 'IIIF-PHP-Validator/1.0';
    
    public function validateWithService($manifestUrl, $service = 'iiif_validator')
    {
        if (!isset($this->validationServices[$service])) {
            throw new InvalidArgumentException("Unknown validation service: $service");
        }
        
        $serviceUrl = $this->validationServices[$service];
        
        switch ($service) {
            case 'iiif_validator':
                return $this->validateWithIIIFValidator($serviceUrl, $manifestUrl);
            case 'github_validator':
                return $this->validateWithGitHubValidator($serviceUrl, $manifestUrl);
            default:
                throw new InvalidArgumentException("Service $service not implemented");
        }
    }
    
    private function validateWithIIIFValidator($serviceUrl, $manifestUrl)
    {
        $postData = json_encode([
            'url' => $manifestUrl,
            'version' => '2.1' // or '3.0'
        ]);
        
        $response = $this->makeHttpRequest($serviceUrl, 'POST', $postData, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        if ($response === false) {
            return [
                'valid' => false,
                'error' => 'Failed to connect to validation service',
                'service' => 'iiif_validator'
            ];
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid' => false,
                'error' => 'Invalid JSON response from validation service',
                'service' => 'iiif_validator'
            ];
        }
        
        return [
            'valid' => isset($result['okay']) ? $result['okay'] : false,
            'errors' => $result['errors'] ?? [],
            'warnings' => $result['warnings'] ?? [],
            'service' => 'iiif_validator',
            'raw_response' => $result
        ];
    }
    
    
    
    public function validateManifestContent($manifestContent, $service = 'iiif_validator')
    {
        // For content validation, we need to post the manifest directly
        $serviceUrl = $this->validationServices[$service];
        
        $postData = json_encode([
            'data' => $manifestContent,
            'version' => '2.1'
        ]);
        
        $response = $this->makeHttpRequest($serviceUrl, 'POST', $postData, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        
        return json_decode($response, true);
        
    }
    
    
    
    private function makeHttpRequest($url, $method = 'GET', $data = null, $headers = [])
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($response === false || !empty($error)) {
            error_log("cURL Error: $error");
            return false;
        }
        
        if ($httpCode >= 400) {
            error_log("HTTP Error: $httpCode");
            return false;
        }
        
        return $response;
    }
    
    public function getAvailableServices()
    {
        return array_keys($this->validationServices);
    }
    
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }
    
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }
}

// Helper class for formatting validation results
class ValidationResultFormatter
{
    public static function formatResults($results, $format = 'text')
    {
        switch ($format) {
            case 'json':
                return json_encode($results, JSON_PRETTY_PRINT);
            case 'html':
                return self::formatAsHtml($results);
            case 'text':
            default:
                return self::formatAsText($results);
        }
    }
    
    private static function formatAsText($results)
    {
        $output = '';
        
        if (isset($results['valid'])) {
            // Single service result
            $output .= "Validation Result:\n";
            $output .= "Service: " . ($results['service'] ?? 'unknown') . "\n";
            $output .= "Valid: " . ($results['valid'] ? 'Yes' : 'No') . "\n";
            
            if (isset($results['error'])) {
                $output .= "Error: " . $results['error'] . "\n";
            }
            
            if (!empty($results['errors'])) {
                $output .= "\nErrors:\n";
                foreach ($results['errors'] as $error) {
                    $output .= "  - $error\n";
                }
            }
            
            if (!empty($results['warnings'])) {
                $output .= "\nWarnings:\n";
                foreach ($results['warnings'] as $warning) {
                    $output .= "  - $warning\n";
                }
            }
        } else {
            // Multiple services results
            foreach ($results as $service => $result) {
                $output .= "=== $service ===\n";
                $output .= self::formatAsText($result);
                $output .= "\n";
            }
        }
        
        return $output;
    }
    
    private static function formatAsHtml($results)
    {
        $html = '<div class="validation-results">';
        
        if (isset($results['valid'])) {
            // Single service result
            $statusClass = $results['valid'] ? 'valid' : 'invalid';
            $html .= "<div class='result $statusClass'>";
            $html .= "<h3>Validation Result</h3>";
            $html .= "<p><strong>Service:</strong> " . htmlspecialchars($results['service'] ?? 'unknown') . "</p>";
            $html .= "<p><strong>Valid:</strong> " . ($results['valid'] ? 'Yes' : 'No') . "</p>";
            
            if (isset($results['error'])) {
                $html .= "<p class='error'><strong>Error:</strong> " . htmlspecialchars($results['error']) . "</p>";
            }
            
            if (!empty($results['errors'])) {
                $html .= "<h4>Errors:</h4><ul>";
                foreach ($results['errors'] as $error) {
                    $html .= "<li>" . htmlspecialchars($error) . "</li>";
                }
                $html .= "</ul>";
            }
            
            if (!empty($results['warnings'])) {
                $html .= "<h4>Warnings:</h4><ul>";
                foreach ($results['warnings'] as $warning) {
                    $html .= "<li>" . htmlspecialchars($warning) . "</li>";
                }
                $html .= "</ul>";
            }
            
            $html .= "</div>";
        } else {
            // Multiple services results
            foreach ($results as $service => $result) {
                $html .= "<h3>" . htmlspecialchars($service) . "</h3>";
                $html .= self::formatAsHtml($result);
            }
        }
        
        $html .= '</div>';
        return $html;
    }
}

// Usage examples
function validateIIIFManifestWithService($manifestUrl, $service = 'iiif_validator')
{
    $validator = new IIIFServiceValidator();
    
    try {
        $result = $validator->validateWithService($manifestUrl, $service);
        echo ValidationResultFormatter::formatResults($result, 'text');
        return $result['valid'];
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    }
}

function validateWithMultipleServices($manifestUrl)
{
    $validator = new IIIFServiceValidator();
    
    $results = $validator->validateMultipleServices($manifestUrl);
    echo ValidationResultFormatter::formatResults($results, 'text');
    
    // Return true only if all services validate successfully
    foreach ($results as $result) {
        if (!$result['valid']) {
            return false;
        }
    }
    return true;
}

function validateManifestFile($filePath)
{
    if (!file_exists($filePath)) {
        echo "Error: File not found: $filePath\n";
        return false;
    }
    
    $manifestContent = file_get_contents($filePath);
    if ($manifestContent === false) {
        echo "Error: Could not read file: $filePath\n";
        return false;
    }
    
    $validator = new IIIFServiceValidator();
    $result = $validator->validateManifestContent($manifestContent);
    
    echo ValidationResultFormatter::formatResults($result, 'text');
    return $result['valid'];
}

// Example usage:
// validateIIIFManifestWithService('https://example.com/manifest.json');
// validateWithMultipleServices('https://example.com/manifest.json');
// validateManifestFile('path/to/manifest.json');

?>
}