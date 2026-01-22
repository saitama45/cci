# 🏗️ GalgaTech Horizon ERP: Full Database & Logic Blueprint

**Target:** Community Creators, Inc. (CCI)

**Engine:** Microsoft SQL Server (MSSQL)

**Primary Currency Type:** `DECIMAL(18, 4)` (Standard for high-precision accounting)

---

## Module 1: Project & Inventory (The Foundation)

These tables manage the physical assets of CCI (Amiya Raya, Shanti, etc.).

### 1.1 `Projects`

| Column | Data Type | Description |
| --- | --- | --- |
| `Id` | `INT IDENTITY(1,1)` | Primary Key |
| `Code` | `NVARCHAR(20)` | Unique project code (e.g., 'AR-RIZAL') |
| `Name` | `NVARCHAR(100)` | Full Project Name |
| `Location` | `NVARCHAR(MAX)` | Physical address |
| `MapOverlay` | `NVARCHAR(MAX)` | Base64 or URL of the SVG site plan |
| `IsActive` | `BIT` | Active/Inactive status |

### 1.2 `Units` (The Lots)

| Column | Data Type | Description |
| --- | --- | --- |
| `Id` | `INT IDENTITY(1,1)` | Primary Key |
| `ProjectId` | `INT` | Foreign Key to `Projects` |
| `BlockNum` | `INT` | Block Number |
| `LotNum` | `INT` | Lot Number |
| `Name` | `NVARCHAR(100)` | Full Unit Model Name |
| `SqmArea` | `DECIMAL(10,2)` | Total square meters |
| `Status` | `NVARCHAR(20)` | Available, Reserved, Sold, Blocked |
| `SvgPath` | `NVARCHAR(MAX)` | The SVG `<path>` data for the interactive map |

### 1.3 `PriceLists`

| Column | Data Type | Description |
| --- | --- | --- |
| `Id` | `INT IDENTITY(1,1)` | Primary Key |
| `UnitId` | `INT` | Foreign Key to `Units` |
| `PricePerSqm` | `DECIMAL(18,4)` | Base price |
| `TCP` | `DECIMAL(18,4)` | Total Contract Price (Calculated) |
| `VatAmount` | `DECIMAL(18,4)` | Computed 12% VAT |
| `EffectiveDate` | `DATE` | When this price takes effect |

---

## Module 2: Sales & Reservations

Handles the transition from lead to buyer.

### 2.1 `Customers`

| Column | Data Type | Description |
| --- | --- | --- |
| `Id` | `INT IDENTITY(1,1)` | Primary Key |
| `FirstName` | `NVARCHAR(50)` |  |
| `LastName` | `NVARCHAR(50)` |  |
| `TIN` | `NVARCHAR(20)` | Required for BIR reporting |
| `Email` | `NVARCHAR(100)` | Used for automated SOAs |
| `MacedaStatus` | `BIT` | Flag for Maceda Law protection |

### 2.2 `Reservations`

| Column | Data Type | Description |
| --- | --- | --- |
| `Id` | `INT IDENTITY(1,1)` | Primary Key |
| `CustomerId` | `INT` |  |
| `UnitId` | `INT` |  |
| `BrokerId` | `INT` |  |
| `ReservationDate` | `DATETIME2` |  |
| `ExpiryDate` | `DATE` | Usually 30 days after reservation |
| `Fee` | `DECIMAL(18,4)` | Non-refundable fee |

### 2.3 `Brokers` & `Commissions`

| Column | Data Type | Description |
| --- | --- | --- |
| `Id` | `INT IDENTITY(1,1)` |  |
| `Name` | `NVARCHAR(100)` |  |
| `CommissionRate` | `DECIMAL(5,2)` | Percentage (e.g., 5.00) |
| `PrcLicense` | `NVARCHAR(50)` | License number for legal audit |

---

## Module 3: Collections & Accounts Receivable

This is the "Brain" of the system.

### 3.1 `Ledger` (The Core Ledger)

| Column | Data Type | Description |
| --- | --- | --- |
| `Id` | `BIGINT IDENTITY` |  |
| `ReservationId` | `INT` | Links to the sale |
| `DueDate` | `DATE` | Monthly amortization date |
| `AmountDue` | `DECIMAL(18,4)` | Total for that month |
| `Principal` | `DECIMAL(18,4)` | Part of payment for the lot |
| `Interest` | `DECIMAL(18,4)` | In-house financing interest |
| `Penalty` | `DECIMAL(18,4)` | Accrued late fees |
| `Status` | `NVARCHAR(20)` | UNPAID, PAID, PARTIAL, OVERDUE |
| `MacedaMonths` | `INT` | Counter for Maceda Law (Months Paid) |

### 3.2 `PdcVault` (Check Management)

| Column | Data Type | Description |
| --- | --- | --- |
| `Id` | `INT IDENTITY` |  |
| `LedgerId` | `BIGINT` | Which month this check covers |
| `CheckNum` | `NVARCHAR(50)` |  |
| `BankName` | `NVARCHAR(50)` |  |
| `CheckDate` | `DATE` | Post-dated date |
| `Status` | `NVARCHAR(20)` | ON-HAND, DEPOSITED, CLEARED, BOUNCED |

### 3.3 `OfficialReceipts` (BIR Compliance)

| Column | Data Type | Description |
| --- | --- | --- |
| `Id` | `INT IDENTITY` |  |
| `OrNumber` | `NVARCHAR(50)` | Serialized Official Receipt |
| `PaymentDate` | `DATETIME2` |  |
| `Amount` | `DECIMAL(18,4)` |  |
| `PaymentMode` | `NVARCHAR(20)` | CASH, CHECK, ONLINE |
| `BirHash` | `NVARCHAR(MAX)` | Digital signature for 2026 E-Invoicing |

---

## Module 4: Accounting & Finance (GL)

Ensures CCI can pass audits and track Project Costs.

### 4.1 `ChartOfAccounts`

| Column | Data Type | Description |
| --- | --- | --- |
| `AccCode` | `NVARCHAR(20)` | Primary Key (e.g., '1010-CASH') |
| `AccName` | `NVARCHAR(100)` |  |
| `AccType` | `NVARCHAR(50)` | ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE |

### 4.2 `JournalEntries` & `Details`

| Column | Data Type | Description |
| --- | --- | --- |
| `EntryId` | `BIGINT IDENTITY` |  |
| `EntryDate` | `DATETIME2` |  |
| `Reference` | `NVARCHAR(100)` | OR number or Voucher number |
| `AccCode` | `NVARCHAR(20)` | Foreign Key to COA |
| `Debit` | `DECIMAL(18,4)` |  |
| `Credit` | `DECIMAL(18,4)` |  |

---

## Module 5: System Admin & Security

### 5.1 `AuditLogs`

| Column | Data Type | Description |
| --- | --- | --- |
| `LogId` | `BIGINT IDENTITY` |  |
| `UserId` | `INT` |  |
| `Action` | `NVARCHAR(MAX)` | e.g., 'CHANGED UNIT STATUS AR-101 FROM AVAILABLE TO RESERVED' |
| `Timestamp` | `DATETIME2` | `DEFAULT GETDATE()` |

---

# 🛠️ Strategy for Coding Assistant (.md Instructions)

"Create a Laravel 11 project with MSSQL backend.

1. Use **Migrations** to build the tables above.
2. Ensure `DECIMAL(18,4)` is used for all money columns.
3. Implement **Maceda Law Logic**: If `Ledger.MacedaMonths` >= 24, apply grace periods.
4. Implement **Interactive SVG Map**: Fetch `Units.SvgPath` and render in Vue 3 with Tailwind hover effects.
5. Create a **PDC Clearing Job**: A daily scheduled task that alerts the 'Collections' role of checks due for deposit tomorrow."

**Would you like me to generate the specific "Calculated View" SQL script that shows the Real-time Aging of Accounts (30/60/90 days overdue) for the Collections dashboard?**