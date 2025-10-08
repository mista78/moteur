# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a VBA-based Excel automation project for calculating French medical professional sick leave benefits ("Indemnités Journalières" - IJ). The system handles complex calculations based on contribution class, age, prior affiliation periods, and pathology status.

## Core Architecture

### Main Entry Point
- `Sub IJ()` (code.vba:20): Primary calculation routine that orchestrates the entire IJ calculation workflow

### Calculation Flow
1. **Age calculation**: `ageDate()` determines age at specific date
2. **Date consolidation**: Automatic merging of consecutive work stoppage periods (code.vba:34-46)
3. **Rights determination**: `datesEffetsDroits()` calculates when benefit rights begin (90-day rule, relapses, etc.)
4. **Days calculation**: `calculNbJours()` determines payable days from rights start date to current payment period
5. **Amount calculation**: `calculMontant()` computes benefit amounts using tiered rates
6. **Rate selection**: `selectStatut()` routes to appropriate rate table based on professional status

### Professional Status Types
- **M** (Médecins): Doctors with classes A/B/C
- **RSPM**: Special medical regime with 0.25% or 1% contribution options
- **CCPL**: Complementary coverage with 0.25% or 0.5% contribution options

### Rate Calculation Logic
The system uses tiered rates based on:
- **Age brackets**: <62, 62-69, 70+
- **Cumulative days**: Three annual periods (0-365, 366-730, 731-1095 days)
- **Pathology anterior status**: Additional rate adjustments for members with prior pathology and limited affiliation quarters
- **Year**: Historical rate tables for 2022-2025

Rate functions:
- `medecinTaux()` (code.vba:622): Reads rates from "Taux médecins" sheet
- `RSPMTaux()` (code.vba:678): Reads rates from "Taux RSPM" sheet
- `CCPLTaux()` (code.vba:646): Reads rates from "Taux CCPL" sheet

### Key Business Rules
- **90-day threshold**: Benefits begin after 90 cumulative days of work stoppage
- **Relapse handling**: Different start rules for relapses (1st day vs 15th day)
- **3-year maximum**: 1095 days maximum indemnity period
- **70+ age limit**: Maximum 365 days per affiliation for members 70 and older
- **8 quarters minimum**: No benefits if less than 8 quarters of affiliation
- **Class change adjustments**: `gestionChangeClasse()` handles retroactive adjustments when contribution class changes

## Data Files

### taux.csv
CSV file containing historical daily benefit rates with columns:
- Date ranges (date_start, date_end)
- Rates for classes A/B/C across three annual tiers (taux_a1/a2/a3, taux_b1/b2/b3, taux_c1/c2/c3)

### mock.json
Test data structure with work stoppage periods including:
- `arret-from-line`, `arret-to-line`: Work stoppage dates
- `rechute-line`: Relapse indicator (0/1)
- `dt-line`, `gpm-member-line`: Declaration tracking
- `declaration-date-line`: Official declaration date

## Excel Worksheet Interface

The VBA code interacts with Excel cells at specific locations:

### Input Cells (Row 3)
- (3,9): Contribution class (A/B/C)
- (3,10): Contribution option percentage
- (3,11): Birth date
- (3,12): Attestation date
- (3,14): Current date / calculation date
- (3,15): Professional status (M/RSPM/CCPL)
- (3,16): Last payment date
- (3,17): Prior pathology flag (O/N)
- (3,18): Affiliation date
- (3,19): Affiliation quarters count

### Work Stoppage Rows (starting row 3)
- Column 1-2: Start and end dates
- Column 3: Unexcused medical certificate flag (N)
- Column 4: Unexcused certificate date
- Column 5: Relapse flag (O/N)
- Column 6: Rights start date (calculated or forced)
- Column 7-8: Contribution account update flag and date

### Output Cells
- (7,14): Age at calculation date
- (10,14): Number of indemnifiable days
- (10,15): Rate breakdown detail string
- (13,14): Total benefit amount
- (16,8): Previously accumulated days
- (16,14): Total cumulative days
- (16,18), (19,18), (22,18): Tiered period end dates
- (19,14): Class change adjustment amount
- (19,8): Previous contribution class

### Utility Functions
- `viderRes()` (code.vba:1): Clears result cells for fresh calculation

## Trimester Calculation

**Business Rule**: Trimesters are counted by quarter periods (Q1-Q4). If the affiliation date falls within a quarter, that quarter counts as **complete**.

**Quarter Definitions**:
- Q1: January 1 - March 31
- Q2: April 1 - June 30
- Q3: July 1 - September 30
- Q4: October 1 - December 31

**Example**: Affiliation on 2019-01-15 (mid-Q1) to 2024-04-11 (Q2) = 22 complete quarters
- Calculation: (5 years × 4) + (Q2 - Q1) + 1 = 20 + 1 + 1 = 22 ✓

This rule is critical for pathology anterior rate determinations (8-15 trimesters = -1/3, 16-23 = -2/3, 24+ = full rate).

**Implementation**: `DateService::calculateTrimesters()` (Services/DateService.php:31)
