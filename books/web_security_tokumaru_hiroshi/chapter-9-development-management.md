# Chapter 9 - Development Management for Secure Web Applications

> From "Safe Web Application Development" (2nd Edition, 2018) by Tokumaru Hiroshi

This chapter explains the management practices necessary for secure application development. The primary audience is application clients (who commission development) and project managers.

---

## 9.1 Overview of Security Measures in Development Management

Development management must address security from two perspectives: **development structure** (organizational) and **development process** (workflow).

### Table 9-1: Security Measures Across the Development Lifecycle

| Phase | Client-Side Focus | Developer-Side Focus | Section |
|-------|------------------|---------------------|---------|
| **Project Initiation** | Understand security requirements for the application | Establish development standards, educate team members | 9.2 |
| **Planning** | Identify important security features, budget for RFI as needed | Use RFI to propose security features at the planning stage | 9.3.1 |
| **Ordering / Contracting** | Formalize security requirements, create detailed RFP with security specs | Clarify security requirements and cost allocation, propose countermeasures | 9.3.2 |
| **Requirements Definition** | Verify security requirements, confirm coverage of OWASP Top 10, etc. | Build project-specific development standards from base standards | 9.3.3 |
| **Design** | Review security architecture at design checkpoints | Verify design against security standards through code/design review | 9.3.4 |
| **Detailed Design** | Design review by development standard compliance check | Code review by development standard compliance check | 9.3.5 |
| **Programming** | Code review by development standard compliance check | Code review by development standard compliance check | 9.3.5 |
| **Testing** | Security testing to verify vulnerability countermeasures; third-party testing | Security testing to verify vulnerability countermeasures | 9.3.7 |
| **Acceptance** | Security requirements verified during acceptance inspection | -- | 9.3.8 |
| **Operations / Maintenance** | Vulnerability information monitoring, patch application | -- | 9.3.9 |

---

## 9.2 Development Structure

### Establishing Development Standards

For secure application development, establishing the development structure is critical. The development structure consists of two elements: **documentation** (standards/guidelines) and **people** (team with appropriate skills).

Based on the author's consulting experience, organizations that invest heavily in secure development tend to have well-maintained development standards (security guidelines/checklists). Good development standards share these traits:

- **Not overly thick** -- focus on high-priority items
- **Reference pages and sources are easy to find**
- **Clear on what to implement**
- **Continuously improved and updated**

Having development standards also helps reduce costs: even without in-house capability, organizations can use them during outsourcing. When commissioning development, security requirements can be documented as part of the contract.

### Key Items to Document in Development Standards

- Countermeasures for each vulnerability type
- Authentication, session management, log output methods
- Review and testing approach for each phase (when, where, what, how)
- Release/deployment criteria (who decides, when, based on what)

### Education

Even with well-established development standards, few organizations maintain and enforce them properly. Adoption often depends on whether the division manager understands the importance. Knowledge may be siloed, with only certain people knowing the standards while many others remain unaware.

#### Two Approaches to Education

1. **Improve the development standards themselves** (make them accessible)
2. **Train the team** on development standards

#### Key Educational Content

- Design review and code review for compliance checking
- **Case studies** of security incidents (to build motivation)
- **Principles and impact** of major vulnerabilities
- **Required compliance items**

#### Role of Security Personnel

Security-focused team members should be cultivated within the organization. Their key responsibilities include:

- Creating and maintaining development standards
- Incident response management
- Participating in reviews
- Security testing
- Monitoring vulnerability information

By centering security personnel in standards improvement and providing ongoing education, organizations can build the capability to develop secure web applications.

---

## 9.3 Development Process

This section explains security considerations at each phase of the development process. The discussion assumes outsourced (contracted) development but also applies to in-house development. For waterfall-to-agile adaptation, see Section 9.3.10.

### 9.3.1 Planning Phase

At the planning phase, estimate the **budget** necessary for secure application development and ensure funding is secured.

To prepare accurate estimates, investigate what security measures are needed. When outsourcing or evaluating external security products, create an **RFI (Request for Information)** to:

- Provide vendors with an overview of the application's security requirements
- Gather information on necessary measures, estimated effort, and costs

The RFI helps vendors submit accurate proposals. It also helps assess vendor quality and capability at this early stage.

### 9.3.2 Ordering / Contracting Phase

When commissioning application development, create an **RFP (Request for Proposal)** that includes security requirements. The RFP forms the basis for quotes, so including security requirements is essential.

Key principles:

- **Separate security functionality (features) from security bugs (vulnerabilities)** in the requirements -- this clarifies cost allocation
- Security functionality decisions should be made during planning; vulnerability countermeasures should be addressed through development standards and testing
- Security requirements for vulnerabilities are quality requirements, and the RFP's requirements should be verifiable/testable

**What to include in the RFP:**

- List of required vulnerability countermeasures
- Specify testing methods and standards
- Request that additional necessary countermeasures be proposed by the contractor
- Request security testing methodology proposals with test results as deliverables
- Clarify remediation responsibility and cost allocation for post-release vulnerabilities
- Request explanation of the development team structure
- Request development standards and security test report samples

> **Real-world example:** In the government sector, the J-LIS (Local Government Information Systems Agency) established security requirements for local government web applications. However, since only "what not to do" was specified without remediation details, additional costs for fixes were sometimes disputed. This is common in the public sector, and such issues also arise in private enterprises.

#### Column: Responsibility for Vulnerabilities

Who bears responsibility for vulnerabilities depends on the contract. In government outsourcing, the contractor generally bears responsibility. In private-sector outsourcing, the responsibility depends on whether the security requirement was explicitly stated in the specification. If it was not explicitly documented, vulnerability responsibility becomes contentious. The court case involving the "Information System Model Contract" framework is instructive: the system vendor was held partially responsible even though SQL injection prevention was not explicitly in the contract, because it was considered a well-known, industry-standard practice.

**Key references:**
- SQL injection responsibility: the Tokyo District Court ruled (2014) that SQL injection countermeasures should have been implemented as standard practice, awarding approximately 22.62 million yen in damages
- IPA publishes security implementation guidelines that can serve as the baseline for "standard security practices"

### 9.3.3 Requirements Definition Phase

From the requirements definition phase onward, the work is primarily done by the developer. During requirements definition:

- Use the client's (commissioning company's) development standards as a baseline
- Supplement with the developer's own standards where the client's standards are lacking
- Since the developer's internal standards may exceed what the client pays for, negotiate scope

**Key requirements items to address:**

- Authentication, account management, authorization requirements (Sections 5.1-5.3)
- Log management requirements (Section 5.4)
- Other security functionality requirements
- Base software selection and patch management policy (Section 8.1.3)
- Gap analysis between development standards and project security requirements

#### Figure 9-2: Building Project Development Standards from Base Standards

The process flow:

1. **Client's security requirements** + **RFP** feed into the project
2. Compare with **internal development standards** (both client and developer)
3. Perform **gap analysis** between the two
4. Create **project-specific development standards** that incorporate the security requirements
5. These then inform **detailed design** and **architectural design** decisions

### 9.3.4 Design Phase

In the design phase, security features are handled the same way as regular features -- designed, developed, and tested.

For security bugs/vulnerabilities, take the project-specific development standards defined during requirements and refine them to a level where programmers can implement them directly, specifying the security testing approach and methodology.

**Key design items:**

- Concrete specification of security features
- Detailed development standards, test methodology decisions

**Screen design security considerations:**

- Identify screens requiring CSRF countermeasures
- Identify screens that must use HTTPS
- Identify screens requiring authorization/access control

### 9.3.5 Detailed Design and Programming Phase

From the detailed design phase onward, follow the design specifications and develop. At each phase/milestone, conduct **design reviews** and **code reviews** to verify that development standards and coding practices are being followed. Reviews can be sampling-based (not necessarily exhaustive) and still be effective.

### 9.3.6 Security Testing: Importance and Methods

Both security bugs and security features must ultimately be verified through testing. Developers should test for security, and clients should verify that security requirements are met.

**Security testing** (vulnerability testing / vulnerability assessment) methods include:

| Method | Description |
|--------|-------------|
| **Expert manual testing** | High accuracy, deep coverage, but expensive |
| **Commercial scanning tools** | Tools like IBM Security AppScan, HP WebInspect; requires significant initial investment (millions of yen) |
| **Self-assessment using free tools** | Tools like OWASP ZAP; initially may lack expertise but has become more accessible |

The perception that "only experts can perform security testing" is outdated. Free tools like OWASP ZAP have made vulnerability assessment accessible to development teams. Paid training courses and free community events are available to build skills.

### 9.3.7 Developer-Side Testing

Within the development process, few teams currently perform dedicated security testing, but this is improving. Approaches:

- Start with automated scanning (e.g., OWASP ZAP) and free tools for source code analysis
- Progress to manual testing as the team matures
- Security testing can be done at page/screen level, making it compatible with agile and iterative development

### 9.3.8 Client-Side Testing (Acceptance)

During acceptance, the client verifies that security requirements from the original specification are met. Methods for acceptance security testing:

1. **Accept the developer's security test reports** (document review)
2. **Commission a third-party (specialist) security assessment**
3. **Perform self-assessment**

For the first option, accepting the developer's own report carries some risk of bias. Engaging a third-party specialist is recommended but becomes more expensive with each engagement. If budget is limited, use tools like OWASP ZAP for self-assessment.

### 9.3.9 Operations Phase

After acceptance, the application enters the operations/maintenance phase. Key security concerns:

#### Log Monitoring and Vulnerability Response

- **Monitor logs** for suspicious activity
- **Respond to vulnerabilities** by applying patches promptly

#### Periodic Vulnerability Assessment

Conduct vulnerability assessments periodically (annually or semi-annually). Purposes include:

- Discovering new pages/features added after deployment that may have vulnerabilities
- Checking for newly discovered attack methods or vulnerability types
- Verifying platform and application vulnerability status

**Log monitoring importance:** As explained in Section 5.4, tools like iLogScanner can analyze access logs for attack patterns. For platforms, follow the guidance from Section 8.1 on web server attack trends and countermeasures.

#### Determining When to Assess

- **Periodic assessment on a schedule** (e.g., yearly)
- **Triggered by external vulnerability disclosures** (e.g., new CVEs)

In any case, promptly addressing vulnerabilities when discovered is critical. Prioritize based on severity and exploitability, using a patch management/incident response framework.

### 9.3.10 Adapting to Agile Development Processes

The security measures described above can also be applied to agile development, but the idea that "agile development is incompatible with security" is a misconception. Security can be integrated at the iteration/sprint level.

**Key principles for agile security:**

- Implement **security policies and standards outside of iterations** -- define once, apply consistently
- **Automate testing** wherever possible, including security tests
- Start security practices early and make them part of the continuous development flow

#### Security Priorities and Scheduling

For web application security, some items are mandatory and some are optional:

- **Mandatory items** (e.g., preventing SQL injection, XSS) must be implemented before release
- **Optional/additional items** can be deferred to post-release iterations
- For example, at the "beta release" stage, private information handling requires mandatory security, while some additional protections (e.g., two-factor authentication, WAF) can be added after launch based on risk assessment

**Risk-based approach:** Prioritize using risk analysis. Break security tasks into smaller units, assign them to iterations, and plan the security testing schedule accordingly.

#### Security Features (Requirements)

Security features introduced in Chapter 5 can generally be decided at the overall project level. However, mandatory items like authentication and authorization must be implemented before release. Security-enhancing features (nice-to-haves) can be scheduled for later releases.

#### Platform Security

Platform-level security (Chapter 8) does not change significantly between waterfall and agile. Security solutions like WAF and IPS can be introduced later based on usage patterns and threat assessment.

#### Vulnerability Testing in Agile

Because agile development has frequent releases, performing a full application-wide vulnerability assessment for every release is impractical. Instead:

- Use **general-purpose vulnerability scanning tools** (e.g., OWASP ZAP's API)
- Use **tools specialized for continuous testing** integrated into the CI/CD pipeline
- Use **source code analysis tools** for static analysis

Continuous vulnerability testing is still an evolving area. The balance between development speed and security in agile teams is an ongoing challenge, and significant improvements in tooling and practices are expected.

---

## 9.4 Summary

This chapter covered development management for secure web applications. The two pillars are:

1. **Organizational structure** -- Establishing development guidelines, educating team members
2. **Development process** -- Integrating security into every phase of the SDLC, from planning through operations

When commissioning web application development, include security requirements in the RFP and other contract documents. As a client, conduct independent security assessments to verify that requirements have been met.

---

## Key Takeaways

| Area | Key Points |
|------|-----------|
| **Standards** | Create concise, actionable development standards; keep them updated; make them accessible |
| **Education** | Train teams on vulnerability principles, case studies, and compliance requirements |
| **Planning** | Budget for security from the start; use RFI to gather vendor information |
| **Contracting** | Separate security features from vulnerability requirements in RFP; clarify responsibility |
| **Requirements** | Perform gap analysis between client standards and project needs; build project-specific standards |
| **Design** | Specify security features concretely; identify CSRF/HTTPS/authorization requirements per screen |
| **Programming** | Conduct code reviews against development standards; use sampling if full review is impractical |
| **Testing** | Use OWASP ZAP and similar tools; consider third-party assessments for critical applications |
| **Acceptance** | Verify security requirements are met; do not rely solely on developer self-reports |
| **Operations** | Monitor logs, apply patches promptly, conduct periodic vulnerability assessments |
| **Agile** | Automate security tests; define security policies outside sprints; prioritize mandatory vs. optional |

## Security Integration Checklist by SDLC Phase

- [ ] **Planning:** Security budget allocated; RFI sent to vendors if outsourcing
- [ ] **Ordering:** RFP includes vulnerability countermeasure list, testing requirements, remediation responsibilities
- [ ] **Requirements:** Gap analysis completed; project-specific development standards created
- [ ] **Design:** Screen-level security requirements identified (CSRF, HTTPS, authorization)
- [ ] **Detailed Design:** Development standards refined to implementation-level detail
- [ ] **Programming:** Code reviews performed against security standards
- [ ] **Testing:** Vulnerability assessment performed (manual, tool-based, or both)
- [ ] **Acceptance:** Independent security verification (third-party or self-assessment)
- [ ] **Operations:** Log monitoring active; patch management process in place; periodic assessments scheduled

---

*Book: "Taikeiteki ni Manabu Anzen na Web Application no Tsukurikata" (Learning Systematically: How to Build Safe Web Applications), 2nd Edition. First published March 2011; 2nd edition published June 2018. Author: Tokumaru Hiroshi. Publisher: SB Creative.*
