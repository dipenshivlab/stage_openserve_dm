import React, { useState, useCallback, useEffect } from "react";
import {
  LegacyCard,
  Page,
  Layout,
  TextContainer,
  Text,
  FormLayout,
  TextField,
  Button,
  Spinner,
  Toast,
} from "@shopify/polaris";
import { TitleBar } from "@shopify/app-bridge-react";
import { useTranslation } from "react-i18next";
import { useAuthenticatedFetch } from "../hooks";
import { Dashboard } from "../components";
import { useISPCodeContext } from "../components/ISPCodeContext"; // Importing the context

export default function HomePage() {
  const { t } = useTranslation();
  const fetch = useAuthenticatedFetch();
  const { isISPCode, setIsISPCode } = useISPCodeContext(); // Using the context

  const [isLoading, setIsLoading] = useState(true);
  const [ispCode, setIspCode] = useState("");
  const [error, setError] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isToastError, setIsToastError] = useState(false);

  const handleIspCodeChange = useCallback((value) => {
    setIspCode(value);
    setError(value.trim() === "");
  }, []);

  const getShopIspCode = async () => {
    try {
      const result = await fetch(`/api/getispcode`, { method: "GET" });
      const { success, data } = await result.json();
      if (success && data !== null && data.isp_code) {
        setIsISPCode(true); // Setting isISPCode using the context
        setIspCode(data.isp_code);
      }
    } catch (error) {
      console.error("Error retrieving ISP code:", error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleSubmitIspCode = async () => {
    if (ispCode.trim() !== "") {
      setIsSubmitting(true);
      try {
        const response = await fetch("/api/storeispseller", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ isp_code: ispCode }),
        });
        if (response.ok) {
          const responseData = await response.json();
          if (responseData.success) {
            setIsISPCode(true); // Setting isISPCode using the context
          } else {
            throw new Error(responseData.message || "Unknown error occurred");
          }
        } else {
          throw new Error("Server error");
        }
      } catch (error) {
        console.error("Error submitting ISP code:", error);
        setIsToastError(true);
      } finally {
        setIsSubmitting(false);
        setError(false);
      }
    } else {
      setError(true);
    }
  };

  useEffect(() => {
    getShopIspCode();
  }, []);

  return (
    <Page fullWidth>
      {isLoading ? (
        <Layout>
          <Layout.Section>
            <div
              style={{
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
                height: "100vh",
              }}
            >
              <Spinner size="large" color="teal" />
            </div>
          </Layout.Section>
        </Layout>
      ) : (
        <Page narrowWidth={!isISPCode} title={isISPCode ? "Dashboard" : ""}>
          <TitleBar />
          {!isISPCode ? (
            <Layout>
              <Layout.Section>
                <LegacyCard sectioned>
                  <TextContainer spacing="loose">
                    <Text as="h2" variant="headingMd">
                      {t("HomePage.heading")}
                    </Text>
                    <p>{t("HomePage.startPopulatingYourApp")}</p>
                    <FormLayout>
                      <TextField
                        value={ispCode}
                        onChange={handleIspCodeChange}
                        label="Enter your ISP Code"
                        autoComplete="off"
                        error={error ? "ISP code cannot be blank" : ""}
                      />
                      <Button
                        variant="primary"
                        onClick={handleSubmitIspCode}
                        disabled={isSubmitting}
                      >
                        {isSubmitting ? (
                          <Spinner
                            size="small"
                            color="white"
                            accessibilityLabel="Submitting"
                          />
                        ) : (
                          "Submit"
                        )}
                      </Button>
                    </FormLayout>
                    <p>Please contact support to get your ISP code.</p>
                  </TextContainer>
                </LegacyCard>
              </Layout.Section>
              {isToastError && (
                <Toast
                  content="Something went wrong! 😔. Please contact our support."
                  error
                  onDismiss={() => setIsToastError(false)}
                />
              )}
            </Layout>
          ) : (
            <Dashboard ispCode={ispCode} />
          )}
        </Page>
      )}
    </Page>
  );
}
