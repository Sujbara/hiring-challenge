# PLAN.md 

## Architecture
The System should consist of three steps:
- A pipeline that takes the company name and mailing address from where it is stored (ex. Dataset, database, etc...)
- An AI that will take the company name and mailing address and starts planning its work
- The AI Agent will extract from the information the company location to know what API to use, and a matching website pattern to search in it
- The Agent will have a list of tools consisting of API calling, Web Scraping,...
- The Agent will search using all available resources before it fails
- The Agent will return the result of the search

## Sources & strategy
1- company's website (checks the name and the address to validate it is the true company)
2- official Government Business Registries
3- official accreditation profiles (Better Business bureau BBB)
4- LinkedIn company detials
5- Sales tools like (ZoomInfo, Apollo, Kaspr, ...)
6- public facing operations like Local news and press releases 

The fallback option is using the mailing address to mail the company for details

## Quality
<!-- Dedupe approach. Your confidence_score logic. Provenance. How you represent "cannot verify". False-positive risk. -->
1- The Agent should be smart in fixing the messiness of the world:
- It should normalize the different role to what is most relevant for our case
- Understand that multiple things can mean one thing
- It should prioritize data based on it source

2- The confidence score is as follows:

- based on the source:
   - From company's official website (direct match)
   - official Government Business Registries
   - official accreditation profiles (Better Business bureau BBB)
   - LinkedIn company detials
   - Sales tools like (ZoomInfo, Apollo, Kaspr, ...)
   - public facing operations like Local news and press releases 

- matches found
- profile recency

The Agent will score the final result by adding all the points for the results

3- Response representation:
The Agent will return a structured JSON the has the role, name, confidence_score, provenance (source, extracted_at, original_raw_text, record_date)
The Agent distingues between Not found, No data, and cannot verify, the score was under 40% for example

4- Handling False-positives:
- The Agent first checks the detials of the company, name and mailing address before any other operations.
- The Agent should also has the latest data, for example after getting a match it should validate the person via LinkedIn to check if he is still in that postion
- The Agent should never use its internal knowledge or hallucinate and provide answers with no data

## Privacy / compliance
- Our agent shouldn't train itself on the data we are getting, ex. use AWS Bedrock
- Only take relevant information
- Clients data should be revokable if they are no longer clients 
- Our sources of information can be accessible (The sources, APIs,...) i.e our provenance

## Clarifying questions
<!-- For EACH question: (a) why it matters, (b) your default assumption if unanswered, (c) what changes in your design depending on the answer. 3 sharp > 15 shallow. -->

1. **Question: What Geographic regions (countries/states) are we covering?**
   - Why it matters: Knowing the relevant APIs to use, handling regulations
   - Default assumption: US-based companies
   - What changes if answered: if global, the agent behaviour should change from validating the mailing address based on different countries, use different APIs

2. **Question: What is the maximum accepted cost of the tool?**
   - Why it matters: knowing the budget helps in allocating the write resources and using the best resources for the business needs
   - Default assumption: the default cost of LLMs is fine
   - What changes if answered: If the budget is tight, we might consider a different architecture of using traditional functions and API calls

3. **Question: Is there existing subscriptions for data that we can use?**
   - Why it matters: The Agent needs data to operate, and not all data is free and accessible
   - Default assumption: No paid data
   - What changes if answered: Use the subscriptions directly in the tools for the agents, if data already exists it would also be faster to get relevant information instead of searching for it

4. **Question: Is this problem often happening or it is one time?**
   - Why it matters: Handling the problem at scale from the beginning
   - Default assumption: It is only one time, the ~1000 clients with no data
   - What changes if answered: Builing message queues, caches, proper database, etc.... that handles high volumes of data
