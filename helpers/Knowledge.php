<?php

declare(strict_types=1);

/**
 * Knowledge Base Helper
 * Manages knowledge base operations with caching and validation
 */

class Knowledge
{
    private string $knowledgeFile;
    private array $knowledgeData = [];
    private int $lastModified = 0;
    private bool $isLoaded = false;

    public function __construct(string $knowledgeFile = null)
    {
        $this->knowledgeFile = $knowledgeFile ?? __DIR__ . '/../knowledge/knowledge.json';
    }

    /**
     * Load knowledge data from JSON file with caching
     */
    private function loadKnowledge(): void
    {
        if (!file_exists($this->knowledgeFile)) {
            throw new RuntimeException('Knowledge file not found: ' . $this->knowledgeFile);
        }

        $currentModified = filemtime($this->knowledgeFile);

        // Check if file has changed or not loaded yet
        if (!$this->isLoaded || $currentModified > $this->lastModified) {
            $content = file_get_contents($this->knowledgeFile);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Invalid JSON in knowledge file: ' . json_last_error_msg());
            }

            // Validate required fields
            $this->validateKnowledgeData($data);

            $this->knowledgeData = $data;
            $this->lastModified = $currentModified;
            $this->isLoaded = true;
        }
    }

    /**
     * Validate knowledge data structure
     */
    private function validateKnowledgeData(array $data): void
    {
        $requiredFields = ['allowed_senders', 'reply_template_text', 'signature'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new RuntimeException("Missing required field: {$field}");
            }
        }

        // Validate allowed_senders is array
        if (!is_array($data['allowed_senders'])) {
            throw new RuntimeException('allowed_senders must be an array');
        }

        // Validate reply_template_text is string
        if (!is_string($data['reply_template_text'])) {
            throw new RuntimeException('reply_template_text must be a string');
        }

        // Validate signature is string
        if (!is_string($data['signature'])) {
            throw new RuntimeException('signature must be a string');
        }
    }

    /**
     * Get allowed sender emails
     */
    public function getAllowedSenders(): array
    {
        $this->loadKnowledge();
        return $this->knowledgeData['allowed_senders'] ?? [];
    }

    /**
     * Get reply template text
     */
    public function getReplyTemplate(): string
    {
        $this->loadKnowledge();
        return $this->knowledgeData['reply_template_text'] ?? '';
    }

    /**
     * Get signature
     */
    public function getSignature(): string
    {
        $this->loadKnowledge();
        return $this->knowledgeData['signature'] ?? '';
    }

    /**
     * Check if email is from allowed sender
     */
    public function isAllowedSender(string $email): bool
    {
        $allowedSenders = $this->getAllowedSenders();
        return in_array(strtolower(trim($email)), array_map('strtolower', $allowedSenders));
    }

    /**
     * Get complete reply with template and signature
     */
    public function getCompleteReply(string $customerName = 'Khách hàng'): string
    {
        $template = $this->getReplyTemplate();
        $signature = $this->getSignature();

        // Replace placeholder with customer name
        $reply = str_replace('[Tên khách hàng]', $customerName, $template);

        return $reply . "\n\n" . $signature;
    }

    /**
     * Force reload knowledge data (clear cache)
     */
    public function reload(): void
    {
        $this->isLoaded = false;
        $this->loadKnowledge();
    }

    /**
     * Get last modified time of knowledge file
     */
    public function getLastModified(): int
    {
        return $this->lastModified;
    }
}
