-- AICDA database schema — identical to the result of all Prisma migrations in
-- AICDA-Backend/prisma/migrations. Only for a NEW, empty database.
-- If you keep using the existing database, do not run this file.

SET NAMES utf8mb4;

CREATE TABLE `Admin` (
    `id` VARCHAR(191) NOT NULL,
    `firstName` VARCHAR(191) NOT NULL,
    `lastName` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(191) NULL,
    `password` VARCHAR(191) NOT NULL,
    `passwordEnc` TEXT NULL,
    `role` ENUM('SUPER_ADMIN') NOT NULL DEFAULT 'SUPER_ADMIN',
    `isActive` BOOLEAN NOT NULL DEFAULT true,
    `createdById` VARCHAR(191) NULL,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updatedAt` DATETIME(3) NOT NULL,
    UNIQUE INDEX `Admin_email_key`(`email`),
    UNIQUE INDEX `Admin_phone_key`(`phone`),
    INDEX `Admin_createdById_fkey`(`createdById`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `Gallery` (
    `id` VARCHAR(191) NOT NULL,
    `title` VARCHAR(191) NOT NULL,
    `description` VARCHAR(191) NULL,
    `imageUrl` VARCHAR(191) NOT NULL,
    `category` ENUM('ASSOCIATION', 'POLITICAL_ACHIEVEMENT', 'IMAGE', 'DIRECTORY', 'LETTER', 'BANNER') NOT NULL,
    `resourceType` ENUM('IMAGE', 'VIDEO') NOT NULL DEFAULT 'IMAGE',
    `isActive` BOOLEAN NOT NULL DEFAULT true,
    `sortOrder` INTEGER NOT NULL DEFAULT 0,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updatedAt` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `Member` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `memberId` VARCHAR(20) NOT NULL, -- text: keeps leading zeros (0560 and 560 are different members)
    `memberName` VARCHAR(191) NOT NULL,
    `fatherName` VARCHAR(191) NULL,
    `dateOfBirth` DATETIME(3) NULL,
    `photo` VARCHAR(191) NULL,
    `residentialAddress` VARCHAR(191) NULL,
    `mobile` VARCHAR(191) NULL,
    `residentialTelephone` VARCHAR(191) NULL,
    `panCardNo` VARCHAR(191) NULL,
    `designation` VARCHAR(191) NULL,
    `companyName` VARCHAR(191) NULL,
    `companyAddress` VARCHAR(191) NULL,
    `companyTelephone` VARCHAR(191) NULL,
    `packetNo` VARCHAR(191) NULL,
    `dateOfJoining` DATETIME(3) NULL,
    `validityFrom` DATETIME(3) NULL,
    `validityTo` DATETIME(3) NULL,
    `aadharNo` VARCHAR(191) NULL,
    `district` VARCHAR(191) NULL,
    `isActive` BOOLEAN NOT NULL DEFAULT true,
    `isShow` BOOLEAN NOT NULL DEFAULT true, -- shown on the public website (old site: activeStatus 0 = shown, 1 = hidden)
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updatedAt` DATETIME(3) NOT NULL,
    `specialDates` JSON NULL,
    `statusReason` TEXT NULL,
    `statusActionBy` VARCHAR(191) NULL,
    `statusActionAt` DATETIME(3) NULL,
    `state` VARCHAR(191) NULL,
    `city` VARCHAR(191) NULL,
    UNIQUE INDEX `Member_memberId_key`(`memberId`),
    INDEX `Member_isActive_idx`(`isActive`),
    INDEX `Member_isShow_idx`(`isShow`),
    INDEX `Member_state_idx`(`state`),
    INDEX `Member_district_idx`(`district`),
    INDEX `Member_city_idx`(`city`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `Partner` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `partnerId` VARCHAR(191) NOT NULL,
    `partnerNumber` INTEGER NOT NULL,
    `memberId` INTEGER NULL, -- optional: some old partners have no member in the old data
    `partnerName` VARCHAR(191) NOT NULL,
    `fatherName` VARCHAR(191) NULL,
    `photo` VARCHAR(191) NULL,
    `residentialAddress` VARCHAR(191) NULL,
    `mobile` VARCHAR(191) NULL,
    `residentialTelephone` VARCHAR(191) NULL,
    `panCardNo` VARCHAR(191) NULL,
    `aadharNo` VARCHAR(191) NULL,
    `designation` VARCHAR(191) NULL,
    `companyName` VARCHAR(191) NULL,
    `companyAddress` VARCHAR(191) NULL,
    `companyTelephone` VARCHAR(191) NULL,
    `packetNo` VARCHAR(191) NULL,
    `dateOfJoining` DATETIME(3) NULL,
    `validityFrom` DATETIME(3) NULL,
    `validityTo` DATETIME(3) NULL,
    `isActive` BOOLEAN NOT NULL DEFAULT true,
    `isShow` BOOLEAN NOT NULL DEFAULT true, -- shown on the public website (old site: activeStatus 0 = shown, 1 = hidden)
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updatedAt` DATETIME(3) NOT NULL,
    `dateOfBirth` DATETIME(3) NULL,
    `district` VARCHAR(191) NULL,
    `specialDates` JSON NULL,
    `statusReason` TEXT NULL,
    `statusActionBy` VARCHAR(191) NULL,
    `statusActionAt` DATETIME(3) NULL,
    `state` VARCHAR(191) NULL,
    `city` VARCHAR(191) NULL,
    UNIQUE INDEX `Partner_partnerId_key`(`partnerId`),
    UNIQUE INDEX `Partner_memberId_partnerNumber_key`(`memberId`, `partnerNumber`),
    INDEX `Partner_isActive_idx`(`isActive`),
    INDEX `Partner_isShow_idx`(`isShow`),
    INDEX `Partner_state_idx`(`state`),
    INDEX `Partner_district_idx`(`district`),
    INDEX `Partner_city_idx`(`city`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `MemberRenewal` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `memberId` INTEGER NOT NULL,
    `amount` DECIMAL(10, 2) NULL,
    `paymentDate` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `validityFrom` DATETIME(3) NOT NULL,
    `validityTo` DATETIME(3) NOT NULL,
    `note` VARCHAR(191) NULL,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    INDEX `MemberRenewal_memberId_idx`(`memberId`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PartnerRenewal` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `partnerId` INTEGER NOT NULL,
    `amount` DECIMAL(10, 2) NULL,
    `paymentDate` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `validityFrom` DATETIME(3) NOT NULL,
    `validityTo` DATETIME(3) NOT NULL,
    `note` VARCHAR(191) NULL,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    INDEX `PartnerRenewal_partnerId_idx`(`partnerId`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `Enquiry` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `requestType` VARCHAR(191) NOT NULL,
    `fullName` VARCHAR(191) NOT NULL,
    `mobile` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NULL,
    `companyName` VARCHAR(191) NULL,
    `city` VARCHAR(191) NULL,
    `state` VARCHAR(191) NULL,
    `message` VARCHAR(191) NULL,
    `status` ENUM('NEW', 'IN_PROGRESS', 'RESOLVED', 'CLOSED') NOT NULL DEFAULT 'NEW',
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updatedAt` DATETIME(3) NOT NULL,
    INDEX `Enquiry_status_idx`(`status`),
    INDEX `Enquiry_createdAt_idx`(`createdAt`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `Admin` ADD CONSTRAINT `Admin_createdById_fkey` FOREIGN KEY (`createdById`) REFERENCES `Admin`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `Partner` ADD CONSTRAINT `Partner_memberId_fkey` FOREIGN KEY (`memberId`) REFERENCES `Member`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `MemberRenewal` ADD CONSTRAINT `MemberRenewal_memberId_fkey` FOREIGN KEY (`memberId`) REFERENCES `Member`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `PartnerRenewal` ADD CONSTRAINT `PartnerRenewal_partnerId_fkey` FOREIGN KEY (`partnerId`) REFERENCES `Partner`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
