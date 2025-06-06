<?php

namespace DoctrineProxies\__CG__\Statistics\Entity;


/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Hit extends \Statistics\Entity\Hit implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Proxy\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Proxy\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array<string, null> properties to be lazy loaded, indexed by property name
     */
    public static $lazyPropertiesNames = array (
);

    /**
     * @var array<string, mixed> default values of properties to be lazy loaded, with keys being the property names
     *
     * @see \Doctrine\Common\Proxy\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = array (
);



    public function __construct(?\Closure $initializer = null, ?\Closure $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }







    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return ['__isInitialized__', 'id', 'url', 'entityId', 'entityName', 'siteId', 'userId', 'ip', 'language', 'query', 'referrer', 'userAgent', 'acceptLanguage', 'created'];
        }

        return ['__isInitialized__', 'id', 'url', 'entityId', 'entityName', 'siteId', 'userId', 'ip', 'language', 'query', 'referrer', 'userAgent', 'acceptLanguage', 'created'];
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Hit $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy::$lazyPropertiesDefaults as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', []);
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load(): void
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', []);
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized(): bool
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized): void
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null): void
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer(): ?\Closure
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null): void
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner(): ?\Closure
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @deprecated no longer in use - generated code now relies on internal components rather than generated public API
     * @static
     */
    public function __getLazyProperties(): array
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', []);

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function setUrl(string $url): \Statistics\Entity\Hit
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setUrl', [$url]);

        return parent::setUrl($url);
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUrl', []);

        return parent::getUrl();
    }

    /**
     * {@inheritDoc}
     */
    public function setEntityId(?int $entityId): \Statistics\Entity\Hit
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setEntityId', [$entityId]);

        return parent::setEntityId($entityId);
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityId(): int
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEntityId', []);

        return parent::getEntityId();
    }

    /**
     * {@inheritDoc}
     */
    public function setEntityName(?string $entityName): \Statistics\Entity\Hit
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setEntityName', [$entityName]);

        return parent::setEntityName($entityName);
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityName(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEntityName', []);

        return parent::getEntityName();
    }

    /**
     * {@inheritDoc}
     */
    public function setSiteId(?int $siteId): \Statistics\Entity\Hit
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSiteId', [$siteId]);

        return parent::setSiteId($siteId);
    }

    /**
     * {@inheritDoc}
     */
    public function getSiteId(): int
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSiteId', []);

        return parent::getSiteId();
    }

    /**
     * {@inheritDoc}
     */
    public function setUserId(?int $userId): \Statistics\Entity\Hit
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setUserId', [$userId]);

        return parent::setUserId($userId);
    }

    /**
     * {@inheritDoc}
     */
    public function getUserId(): int
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUserId', []);

        return parent::getUserId();
    }

    /**
     * {@inheritDoc}
     */
    public function setIp(?string $ip): \Statistics\Entity\Hit
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIp', [$ip]);

        return parent::setIp($ip);
    }

    /**
     * {@inheritDoc}
     */
    public function getIp(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIp', []);

        return parent::getIp();
    }

    /**
     * {@inheritDoc}
     */
    public function setQuery(?array $query): \Statistics\Entity\Hit
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setQuery', [$query]);

        return parent::setQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function getQuery(): ?array
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getQuery', []);

        return parent::getQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function setReferrer(?string $referrer): \Statistics\Entity\Hit
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setReferrer', [$referrer]);

        return parent::setReferrer($referrer);
    }

    /**
     * {@inheritDoc}
     */
    public function getReferrer(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getReferrer', []);

        return parent::getReferrer();
    }

    /**
     * {@inheritDoc}
     */
    public function setUserAgent(?string $userAgent): \Statistics\Entity\Hit
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setUserAgent', [$userAgent]);

        return parent::setUserAgent($userAgent);
    }

    /**
     * {@inheritDoc}
     */
    public function getUserAgent(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUserAgent', []);

        return parent::getUserAgent();
    }

    /**
     * {@inheritDoc}
     */
    public function setAcceptLanguage(?string $acceptLanguage): \Statistics\Entity\Hit
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAcceptLanguage', [$acceptLanguage]);

        return parent::setAcceptLanguage($acceptLanguage);
    }

    /**
     * {@inheritDoc}
     */
    public function getAcceptLanguage(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAcceptLanguage', []);

        return parent::getAcceptLanguage();
    }

    /**
     * {@inheritDoc}
     */
    public function setLanguage(?string $language): \Statistics\Entity\Hit
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLanguage', [$language]);

        return parent::setLanguage($language);
    }

    /**
     * {@inheritDoc}
     */
    public function getLanguage(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLanguage', []);

        return parent::getLanguage();
    }

    /**
     * {@inheritDoc}
     */
    public function setCreated(\DateTime $created): \Statistics\Entity\Hit
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCreated', [$created]);

        return parent::setCreated($created);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreated(): \DateTime
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCreated', []);

        return parent::getCreated();
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getResourceId', []);

        return parent::getResourceId();
    }

}
