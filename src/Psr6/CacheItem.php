<?php

namespace ByJG\Cache\Psr6;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

class CacheItem implements CacheItemInterface
{
    /**
     * @var string
     */
    protected string $key;
    
    /**
     * @var mixed
     */
    protected mixed $value;
    
    /**
     * @var boolean
     */
    protected bool $hit;
    
    /**
     * @var DateTimeInterface
     */
    protected DateTimeInterface $expiration;

    /**
     * CacheItem constructor.
     * @param string $key
     * @param mixed $value
     * @param bool $hit
     */
    public function __construct(string $key, mixed $value, bool $hit = true)
    {
        $this->key = $key;
        $this->value = $value;
        $this->hit = $hit;
        $this->expiresAt(null);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getKey(): string
    {
        return $this->key;
    }
    
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function get(): mixed
    {
        return $this->isHit() ? $this->value : null;
    }
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function set(mixed $value = null): static
    {
        $this->value = $value;
        $this->hit = !is_null($value);
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function isHit(): bool
    {
        return $this->hit;
    }
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function expiresAt(?DateTimeInterface $expiration): static
    {
        if (empty($expiration)) {
            // We need to set a especific date far from now
            $this->expiration = new DateTime('now +99 year');
            return $this;
        }

        $this->expiration = $expiration;
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function expiresAfter(int|\DateInterval|null $time): static
    {
        if (is_numeric($time)) {
            $this->expiresAt(new DateTime('now +' . $time . ' seconds'));
        } else if ($time instanceof DateInterval) {
            $expiration = new DateTime();
            $expiration->add($time);
            $this->expiresAt($expiration);
        } else {
            $this->expiresAt(null);
        }
        return $this;
    }

    /**
     * @return ?DateTime
     */
    public function getExpiresAt(): ?DateTime
    {
        return $this->expiration;
    }
    
    public function getExpiresInSecs(): int
    {
        return $this->getExpiresAt()->getTimestamp() - (new DateTime('now'))->getTimestamp();
    }
}
