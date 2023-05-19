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
    protected $key;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var boolean
     */
    protected $hit;

    /**
     * @var DateTime
     */
    protected $expiration;

    /**
     * CacheItem constructor.
     * @param string $key
     * @param mixed $value
     * @param bool $hit
     */
    public function __construct($key, $value, $hit = true)
    {
        $this->key = $key;
        $this->value = $value;
        $this->hit = $hit;
        $this->expiresAt(null);
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): mixed
    {
        return $this->isHit() ? $this->value : null;
    }
    /**
     * {@inheritdoc}
     */
    public function set(mixed $value = null): static
    {
        $this->value = $value;
        $this->hit = !is_null($value);
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function isHit(): bool
    {
        return $this->hit;
    }
    /**
     * {@inheritdoc}
     */
    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        $this->expiration = new DateTime('now +1 year');
        if ($expiration instanceof DateTimeInterface) {
            $this->expiration = $expiration;
        }
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function expiresAfter(int|\DateInterval|null $time): static
    {
        $this->expiration = new DateTime('now +1 year');
        if (is_numeric($time)) {
            $this->expiration = new DateTime('now +' . $time . ' seconds');
        } else if ($time instanceof DateInterval) {
            $expiration = new DateTime();
            $expiration->add($time);
            $this->expiration = $expiration;
        }
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiration;
    }

    public function getExpiresInSecs()
    {
        return $this->getExpiresAt()->getTimestamp() - (new DateTime('now'))->getTimestamp();
    }
}
