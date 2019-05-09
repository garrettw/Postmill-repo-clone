<?php

namespace App\Form\Model;

use App\Entity\Submission;
use App\Entity\Theme;
use App\Entity\User;
use App\Validator\Constraints\Unique;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Unique("normalizedUsername", idFields={"entityId": "id"}, errorPath="username",
 *     entityClass="App\Entity\User", groups={"registration", "edit"})
 */
class UserData implements UserInterface {
    /**
     * @var int|null
     */
    private $entityId;

    /**
     * @Assert\Length(min=3, max=25, groups={"registration", "edit"})
     * @Assert\NotBlank(groups={"registration", "edit"})
     * @Assert\Regex("/^\w+$/", groups={"registration", "edit"})
     *
     * @var string|null
     */
    private $username;

    /**
     * @var string|null
     */
    private $normalizedUsername;

    private $password;

    /**
     * @Assert\Length(min=8, max=72, charset="8bit", groups={"registration", "edit"})
     *
     * @var string|null
     */
    private $plainPassword;

    /**
     * @Assert\Email(groups={"registration", "edit"})
     *
     * @var string|null
     */
    private $email;

    private $locale;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @Assert\Choice(Submission::FRONT_PAGE_OPTIONS, groups={"settings"}, strict=true)
     * @Assert\NotBlank(groups={"settings"})
     */
    private $frontPage;

    /**
     * @Assert\Choice({Submission::SORT_ACTIVE, Submission::SORT_HOT, Submission::SORT_NEW}, groups={"settings"}, strict=true)
     * @Assert\NotBlank(groups={"settings"})
     */
    private $frontPageSortMode;

    private $showCustomStylesheets;

    /**
     * @var Theme|null
     */
    private $preferredTheme;

    private $openExternalLinksInNewTab;

    /**
     * @Assert\Length(max=300, groups={"edit_biography"})
     *
     * @var string|null
     */
    private $biography;

    private $autoFetchSubmissionTitles;

    private $enablePostPreviews;

    private $showThumbnails;

    private $notifyOnReply;

    private $notifyOnMentions;

    /**
     * @Assert\Length(max=200, groups={"settings"})
     *
     * @var string|null
     */
    private $preferredFonts;

    private $admin = false;

    public static function fromUser(User $user): self {
        $self = new self();
        $self->entityId = $user->getId();
        $self->username = $user->getUsername();
        $self->email = $user->getEmail();
        $self->locale = $user->getLocale();
        $self->timezone = $user->getTimezone();
        $self->frontPage = $user->getFrontPage();
        $self->frontPageSortMode = $user->getFrontPageSortMode();
        $self->showCustomStylesheets = $user->isShowCustomStylesheets();
        $self->preferredTheme = $user->getPreferredTheme();
        $self->openExternalLinksInNewTab = $user->openExternalLinksInNewTab();
        $self->biography = $user->getBiography();
        $self->autoFetchSubmissionTitles = $user->autoFetchSubmissionTitles();
        $self->enablePostPreviews = $user->enablePostPreviews();
        $self->showThumbnails = $user->showThumbnails();
        $self->notifyOnReply = $user->getNotifyOnReply();
        $self->notifyOnMentions = $user->getNotifyOnMentions();
        $self->preferredFonts = $user->getPreferredFonts();
        $self->admin = $user->isAdmin();

        return $self;
    }

    public function updateUser(User $user) {
        $user->setUsername($this->username);

        if ($this->password) {
            $user->setPassword($this->password);
        }

        $user->setEmail($this->email);
        $user->setLocale($this->locale);
        $user->setTimezone($this->timezone);
        $user->setFrontPage($this->frontPage);
        $user->setFrontPageSortMode($this->frontPageSortMode);
        $user->setShowCustomStylesheets($this->showCustomStylesheets);
        $user->setPreferredTheme($this->preferredTheme);
        $user->setOpenExternalLinksInNewTab($this->openExternalLinksInNewTab);
        $user->setBiography($this->biography);
        $user->setAutoFetchSubmissionTitles($this->autoFetchSubmissionTitles);
        $user->setEnablePostPreviews($this->enablePostPreviews);
        $user->setShowThumbnails($this->showThumbnails);
        $user->setNotifyOnReply($this->notifyOnReply);
        $user->setNotifyOnMentions($this->notifyOnMentions);
        $user->setPreferredFonts($this->preferredFonts);
        $user->setAdmin($this->admin);
    }

    public function toUser(): User {
        $user = new User($this->username, $this->password);
        $user->setEmail($this->email);
        $user->setBiography($this->biography);
        $user->setAdmin($this->admin);

        $settings = [
            'showCustomStylesheets',
            'frontPage',
            'frontPageSortMode',
            'locale',
            'timezone',
            'preferredTheme',
            'openExternalLinksInNewTab',
            'autoFetchSubmissionTitles',
            'enablePostPreviews',
            'showThumbnails',
            'notifyOnReply',
            'notifyOnMentions',
            'preferredFonts',
        ];

        foreach ($settings as $setting) {
            if ($this->{$setting} !== null) {
                $user->{'set'.ucfirst($setting)}($this->{$setting});
            }
        }

        return $user;
    }

    /**
     * @return int|null
     */
    public function getEntityId() {
        return $this->entityId;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
        $this->normalizedUsername = isset($username) ? User::normalizeUsername($username) : null;
    }

    public function getNormalizedUsername() {
        return $this->normalizedUsername;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function getPlainPassword() {
        return $this->plainPassword;
    }

    public function setPlainPassword($plainPassword) {
        $this->plainPassword = $plainPassword;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getLocale() {
        return $this->locale;
    }

    public function setLocale($locale) {
        $this->locale = $locale;
    }

    public function getTimezone(): \DateTimeZone {
        return $this->timezone;
    }

    public function setTimezone(\DateTimeZone $timezone): void {
        $this->timezone = $timezone;
    }

    public function getFrontPage() {
        return $this->frontPage;
    }

    public function setFrontPage($frontPage) {
        $this->frontPage = $frontPage;
    }

    public function getFrontPageSortMode() {
        return $this->frontPageSortMode;
    }

    public function setFrontPageSortMode($frontPageSortMode) {
        $this->frontPageSortMode = $frontPageSortMode;
    }

    public function getShowCustomStylesheets() {
        return $this->showCustomStylesheets;
    }

    public function setShowCustomStylesheets($showCustomStylesheets) {
        $this->showCustomStylesheets = $showCustomStylesheets;
    }

    public function getPreferredTheme(): ?Theme {
        return $this->preferredTheme;
    }

    public function setPreferredTheme(?Theme $preferredTheme): void {
        $this->preferredTheme = $preferredTheme;
    }

    public function openExternalLinksInNewTab() {
        return $this->openExternalLinksInNewTab;
    }

    public function setOpenExternalLinksInNewTab($openExternalLinksInNewTab) {
        $this->openExternalLinksInNewTab = $openExternalLinksInNewTab;
    }

    public function getBiography() {
        return $this->biography;
    }

    public function setBiography($biography) {
        $this->biography = $biography;
    }

    public function getAutoFetchSubmissionTitles(): ?bool {
        return $this->autoFetchSubmissionTitles;
    }

    public function setAutoFetchSubmissionTitles(?bool $autoFetchSubmissionTitles): void {
        $this->autoFetchSubmissionTitles = $autoFetchSubmissionTitles;
    }

    public function enablePostPreviews(): bool {
        return $this->enablePostPreviews;
    }

    public function setEnablePostPreviews(bool $enablePostPreviews): void {
        $this->enablePostPreviews = $enablePostPreviews;
    }

    public function showThumbnails() {
        return $this->showThumbnails;
    }

    public function setShowThumbnails($showThumbnails): void {
        $this->showThumbnails = $showThumbnails;
    }

    public function getNotifyOnReply(): ?bool {
        return $this->notifyOnReply;
    }

    public function setNotifyOnReply(bool $notifyOnReply): void {
        $this->notifyOnReply = $notifyOnReply;
    }

    public function getNotifyOnMentions(): ?bool {
        return $this->notifyOnMentions;
    }

    public function setNotifyOnMentions(bool $notifyOnMentions): void {
        $this->notifyOnMentions = $notifyOnMentions;
    }

    public function getPreferredFonts(): ?string {
        return $this->preferredFonts;
    }

    public function setPreferredFonts(?string $preferredFonts): void {
        $this->preferredFonts = $preferredFonts;
    }

    public function isAdmin(): bool {
        return $this->admin;
    }

    public function setAdmin(bool $admin): void {
        $this->admin = $admin;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles() {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt() {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials() {
        $this->plainPassword = null;
    }
}
